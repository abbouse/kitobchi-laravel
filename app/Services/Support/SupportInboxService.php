<?php

namespace App\Services\Support;

use App\Events\SellerSupportTicketUpdated;
use App\Events\SupportInboxUpdated;
use App\Jobs\SendSellerSupportPush;
use App\Models\Admin;
use App\Models\BotOperator;
use App\Models\BotTicket;
use App\Models\BotTicketAttachment;
use App\Models\BotTicketMessage;
use App\Models\Seller;
use App\Models\SellerSupportTicket;
use App\Models\SellerSupportTicketMessage;
use App\Models\Sold;
use App\Models\User;
use App\Services\SessionService;
use App\Services\SupportChatBridgeService;
use App\Services\TelegramSupportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Yagona support inbox: mijozlar (Telegram bot + ilova chati) va do'konlar (seller tiketlari).
 * Operatorlar faqat boshqaruvdan javob beradi.
 *
 * Thread kaliti:
 *   c-bot-{telegramChatId}   — Telegram bot orqali yozgan mijoz
 *   c-app-{userId}           — ilovadagi support chat (shop_chat)
 *   s-{sellerId}             — do'kon (seller_support_tickets)
 */
class SupportInboxService
{
    public const CUSTOMER_OPEN = ['queue', 'active'];
    public const SHOP_OPEN = ['open', 'answered', 'waiting'];
    public const FILTERS = ['all', 'open', 'unassigned', 'mine', 'closed'];

    /** @var array<int, string> */
    private array $adminNames = [];

    // ─── KALITLAR ────────────────────────────────────────────────────────────

    public static function customerKey(object $ticket): string
    {
        $source = ($ticket->source_type ?? 'bot') === 'shop_chat' ? 'app' : 'bot';

        return 'c-' . $source . '-' . (int) $ticket->user_id;
    }

    public static function shopKey(int $sellerId): string
    {
        return 's-' . $sellerId;
    }

    /**
     * @return array{segment: string, source: ?string, id: int}
     */
    public static function parseKey(string $key): array
    {
        if (preg_match('/^c-(bot|app)-(\d+)$/', $key, $m)) {
            return ['segment' => 'customer', 'source' => $m[1] === 'app' ? 'shop_chat' : 'bot', 'id' => (int) $m[2]];
        }
        if (preg_match('/^s-(\d+)$/', $key, $m)) {
            return ['segment' => 'shop', 'source' => null, 'id' => (int) $m[1]];
        }

        throw new InvalidArgumentException('Noto‘g‘ri suhbat kaliti.');
    }

    // ─── RO'YXAT ─────────────────────────────────────────────────────────────

    public function threads(string $segment, string $filter, string $search, Admin $me, ?string $before = null, int $limit = 40): array
    {
        $filter = in_array($filter, self::FILTERS, true) ? $filter : 'all';
        $limit = max(10, min(100, $limit));

        if ($segment === 'shop') {
            $rows = $this->shopListQuery($filter, $search, $me)
                ->when($before, fn ($q) => $q->having('lm', '<', Carbon::parse($before)))
                ->orderByDesc('lm')
                ->limit($limit + 1)
                ->get();
            $hasMore = $rows->count() > $limit;
            $rows = $rows->take($limit);
            $items = $this->shopSummaries($rows);
        } else {
            $tickets = $this->customerListQuery($filter, $search, $me)
                ->when($before, fn ($q) => $q->whereRaw('COALESCE(bot_tickets.last_message_at, bot_tickets.updated_at) < ?', [Carbon::parse($before)]))
                ->orderByRaw('COALESCE(bot_tickets.last_message_at, bot_tickets.updated_at) DESC')
                ->limit($limit + 1)
                ->get();
            $hasMore = $tickets->count() > $limit;
            $tickets = $tickets->take($limit);
            $items = $this->customerSummaries($tickets);
        }

        $last = end($items);

        return [
            'items' => array_values($items),
            'next' => $hasMore && $last ? $last['last_message_at'] : null,
            'counts' => $this->filterCounts($segment, $search, $me),
            'segments' => $this->segmentCounts(),
        ];
    }

    public function segmentCounts(): array
    {
        $customer = DB::table('bot_tickets')
            ->joinSub($this->latestCustomerTicketIds(), 'lt', 'lt.id', '=', 'bot_tickets.id')
            ->selectRaw("SUM(CASE WHEN bot_tickets.status IN ('queue','active') THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw('SUM(bot_tickets.admin_unread_count) AS unread')
            ->first();

        $shop = DB::table('seller_support_tickets')
            ->selectRaw("SUM(CASE WHEN status <> 'closed' THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw('SUM(admin_unread_count) AS unread')
            ->first();

        return [
            'customer' => ['open' => (int) ($customer->open_count ?? 0), 'unread' => (int) ($customer->unread ?? 0)],
            'shop' => ['open' => (int) ($shop->open_count ?? 0), 'unread' => (int) ($shop->unread ?? 0)],
        ];
    }

    private function filterCounts(string $segment, string $search, Admin $me): array
    {
        $counts = [];
        foreach (self::FILTERS as $filter) {
            if ($segment === 'shop') {
                $counts[$filter] = DB::query()->fromSub($this->shopListQuery($filter, $search, $me), 'x')->count();
            } else {
                $counts[$filter] = $this->customerListQuery($filter, $search, $me)->count();
            }
        }

        return $counts;
    }

    private function latestCustomerTicketIds()
    {
        return DB::table('bot_tickets')->selectRaw('MAX(id) AS id')->groupBy('source_type', 'user_id');
    }

    private function customerListQuery(string $filter, string $search, Admin $me): Builder
    {
        $query = BotTicket::query()
            ->joinSub($this->latestCustomerTicketIds(), 'lt', 'lt.id', '=', 'bot_tickets.id')
            ->select('bot_tickets.*');

        match ($filter) {
            'open' => $query->whereIn('bot_tickets.status', self::CUSTOMER_OPEN),
            'unassigned' => $query->whereIn('bot_tickets.status', self::CUSTOMER_OPEN)
                ->whereNull('bot_tickets.admin_id')
                ->where(fn ($q) => $q->whereNull('bot_tickets.operator_id')->orWhere('bot_tickets.operator_id', 0)),
            'mine' => $query->whereIn('bot_tickets.status', self::CUSTOMER_OPEN)->where('bot_tickets.admin_id', $me->id),
            'closed' => $query->whereNotIn('bot_tickets.status', self::CUSTOMER_OPEN),
            default => null,
        };

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], ltrim($search, '@')) . '%';
            $digits = preg_replace('/\D/', '', $search);
            $query->where(function ($q) use ($like, $digits) {
                $q->where('bot_tickets.name', 'like', $like)
                    ->orWhere('bot_tickets.username', 'like', $like)
                    ->orWhere('bot_tickets.first_msg', 'like', $like);
                if ($digits !== '') {
                    $q->orWhere('bot_tickets.user_id', $digits)
                        ->orWhere('bot_tickets.id', $digits)
                        ->orWhere(function ($qq) use ($digits) {
                            $qq->where('bot_tickets.source_type', 'shop_chat')
                                ->whereIn('bot_tickets.user_id', User::query()->select('id')->where('phone_number', 'like', '%' . $digits . '%'));
                        });
                }
                $q->orWhere(function ($qq) use ($like) {
                    $qq->where('bot_tickets.source_type', 'shop_chat')
                        ->whereIn('bot_tickets.user_id', User::query()->select('id')
                            ->where(fn ($u) => $u->where('name', 'like', $like)->orWhere('lastname', 'like', $like)));
                });
            });
        }

        return $query;
    }

    private function shopListQuery(string $filter, string $search, Admin $me)
    {
        $query = DB::table('seller_support_tickets')
            ->select('seller_id')
            ->selectRaw('MAX(id) AS latest_id')
            ->selectRaw('MAX(COALESCE(last_message_at, updated_at, created_at)) AS lm')
            ->selectRaw('SUM(admin_unread_count) AS unread')
            ->selectRaw("SUM(CASE WHEN status <> 'closed' THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw("SUM(CASE WHEN status <> 'closed' AND admin_id IS NOT NULL THEN 1 ELSE 0 END) AS assigned_count")
            ->selectRaw("SUM(CASE WHEN status <> 'closed' AND admin_id = ? THEN 1 ELSE 0 END) AS mine_count", [$me->id])
            ->groupBy('seller_id');

        match ($filter) {
            'open' => $query->havingRaw('open_count > 0'),
            'unassigned' => $query->havingRaw('open_count > 0 AND assigned_count = 0'),
            'mine' => $query->havingRaw('mine_count > 0'),
            'closed' => $query->havingRaw('open_count = 0'),
            default => null,
        };

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $digits = preg_replace('/\D/', '', $search);
            $query->where(function ($q) use ($like, $digits) {
                $q->where('subject', 'like', $like)
                    ->orWhereIn('seller_id', Seller::query()->select('id')->where(function ($s) use ($like, $digits) {
                        $s->where('shop_name', 'like', $like)
                            ->orWhere('firstname', 'like', $like)
                            ->orWhere('lastname', 'like', $like);
                        if ($digits !== '') {
                            $s->orWhere('phone_number', 'like', '%' . $digits . '%')->orWhere('id', $digits);
                        }
                    }));
            });
        }

        return $query;
    }

    /**
     * @param  Collection<int, BotTicket>  $tickets
     */
    private function customerSummaries(Collection $tickets): array
    {
        if ($tickets->isEmpty()) {
            return [];
        }

        $tickets->loadMissing('latestMessage');
        $appUserIds = $tickets->where('source_type', 'shop_chat')->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values();
        $tgIds = $tickets->where('source_type', '!=', 'shop_chat')->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values();

        $appUsers = $appUserIds->isEmpty() ? collect() : User::query()->whereIn('id', $appUserIds)->get()->keyBy('id');
        $tgUsers = $tgIds->isEmpty() ? collect() : User::query()->whereIn('telegram_id', $tgIds)->get()->keyBy(fn ($u) => (int) $u->telegram_id);

        $this->primeAdminNames($tickets->pluck('admin_id')->filter()->all());

        return $tickets->map(function (BotTicket $ticket) use ($appUsers, $tgUsers) {
            $user = $ticket->source_type === 'shop_chat'
                ? $appUsers->get((int) $ticket->user_id)
                : $tgUsers->get((int) $ticket->user_id);

            return $this->customerSummary($ticket, $user);
        })->values()->all();
    }

    public function customerSummary(BotTicket $ticket, ?User $user = null, bool $resolveUser = false): array
    {
        if ($resolveUser && ! $user) {
            $user = $this->resolveCustomerUser($ticket);
        }

        $latest = $ticket->relationLoaded('latestMessage') ? $ticket->latestMessage : $ticket->latestMessage()->first();
        $isApp = $ticket->source_type === 'shop_chat';
        $name = trim((string) ($ticket->name ?: ''));
        if ($user) {
            $userName = trim(($user->name ?? '') . ' ' . ($user->lastname ?? ''));
            $name = $userName !== '' ? $userName : $name;
        }
        if ($name === '') {
            $name = $ticket->username && $ticket->username !== 'empty' ? '@' . $ticket->username : 'Mijoz #' . $ticket->user_id;
        }

        $username = $ticket->username && $ticket->username !== 'empty' ? '@' . ltrim($ticket->username, '@') : null;
        $phone = $user?->phone_number;
        $open = in_array($ticket->status, self::CUSTOMER_OPEN, true);
        $lastAt = $ticket->last_message_at ?: $ticket->updated_at ?: $ticket->created_at;

        return [
            'key' => self::customerKey($ticket),
            'segment' => 'customer',
            'source' => $isApp ? 'app' : 'telegram',
            'ticket_id' => (int) $ticket->id,
            'name' => $name,
            'subtitle' => $phone ?: ($username ?: ($isApp ? 'Ilova' : 'Telegram')),
            'avatar' => $this->mediaUrl($user?->avatar ?: $user?->telegram_photo),
            'last_message' => $latest ? $this->preview($latest->message, $latest->message_type) : (string) $ticket->first_msg,
            'last_from' => $latest ? $this->customerFrom($latest) : 'customer',
            'last_message_at' => optional($lastAt)->toIso8601String(),
            'unread' => (int) ($ticket->admin_unread_count ?? 0),
            'status' => $open ? 'open' : 'closed',
            'raw_status' => $ticket->status,
            'waiting' => $open && $latest && $this->customerFrom($latest) === 'customer',
            'assignee' => $ticket->admin_id ? ['id' => (int) $ticket->admin_id, 'name' => $this->adminName((int) $ticket->admin_id)] : null,
            'legacy_operator' => (! $ticket->admin_id && $ticket->operator_id) ? $this->operatorName((int) $ticket->operator_id) : null,
            'feedback' => $ticket->feedback,
        ];
    }

    private function shopSummaries(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $tickets = SellerSupportTicket::query()->with('latestMessage')->whereIn('id', $rows->pluck('latest_id'))->get()->keyBy('id');
        $sellers = Seller::query()->whereIn('id', $rows->pluck('seller_id'))->get()->keyBy('id');
        $this->primeAdminNames($tickets->pluck('admin_id')->filter()->all());

        return $rows->map(function ($row) use ($tickets, $sellers) {
            $ticket = $tickets->get((int) $row->latest_id);
            if (! $ticket) {
                return null;
            }

            return $this->shopSummary($ticket, $sellers->get((int) $row->seller_id), $row);
        })->filter()->values()->all();
    }

    public function shopSummary(SellerSupportTicket $ticket, ?Seller $seller = null, ?object $aggregate = null): array
    {
        $seller ??= Seller::query()->find($ticket->seller_id);

        if (! $aggregate) {
            $aggregate = DB::table('seller_support_tickets')
                ->where('seller_id', $ticket->seller_id)
                ->selectRaw('MAX(COALESCE(last_message_at, updated_at, created_at)) AS lm')
                ->selectRaw('SUM(admin_unread_count) AS unread')
                ->selectRaw("SUM(CASE WHEN status <> 'closed' THEN 1 ELSE 0 END) AS open_count")
                ->first();
        }

        $openTicket = SellerSupportTicket::query()
            ->where('seller_id', $ticket->seller_id)
            ->where('status', '!=', 'closed')
            ->latest('id')
            ->first();
        $focus = $openTicket ?: $ticket;

        $latest = SellerSupportTicketMessage::query()
            ->whereIn('ticket_id', SellerSupportTicket::query()->select('id')->where('seller_id', $ticket->seller_id))
            ->latest('id')
            ->first();

        $name = trim((string) ($seller?->shop_name ?: trim(($seller?->firstname ?? '') . ' ' . ($seller?->lastname ?? ''))));
        $open = (int) ($aggregate->open_count ?? 0) > 0;
        $lastFrom = $latest ? $this->shopFrom($latest) : 'customer';

        return [
            'key' => self::shopKey((int) $ticket->seller_id),
            'segment' => 'shop',
            'source' => 'shop',
            'ticket_id' => (int) $focus->id,
            'name' => $name !== '' ? $name : 'Do‘kon #' . $ticket->seller_id,
            'subtitle' => $focus->subject ?: ($seller?->phone_number ?: 'Do‘kon'),
            'avatar' => $this->mediaUrl($seller?->photo),
            'last_message' => $latest ? $this->preview($latest->message, $latest->is_internal ? 'note' : 'text') : '',
            'last_from' => $lastFrom,
            'last_message_at' => $aggregate->lm ? Carbon::parse($aggregate->lm)->toIso8601String() : optional($ticket->updated_at)->toIso8601String(),
            'unread' => (int) ($aggregate->unread ?? 0),
            'status' => $open ? 'open' : 'closed',
            'raw_status' => $focus->status,
            'waiting' => $open && $lastFrom === 'customer',
            'assignee' => $focus->admin_id ? ['id' => (int) $focus->admin_id, 'name' => $this->adminName((int) $focus->admin_id)] : null,
            'legacy_operator' => null,
            'feedback' => $focus->feedback,
            'peer_read' => (int) $focus->seller_unread_count === 0,
        ];
    }

    // ─── SUHBAT ──────────────────────────────────────────────────────────────

    public function thread(string $key, Admin $me): array
    {
        $parsed = self::parseKey($key);

        if ($parsed['segment'] === 'shop') {
            $tickets = $this->shopTickets($parsed['id']);
            abort_if($tickets->isEmpty(), 404, 'Suhbat topilmadi.');
            $latest = $tickets->first();
            [$messages, $hasMore] = $this->shopMessages($tickets, null);

            return [
                'thread' => $this->shopSummary($latest),
                'messages' => $messages,
                'has_more' => $hasMore,
                'context' => $this->shopContext($parsed['id'], $tickets),
            ];
        }

        $tickets = $this->customerTickets($parsed['source'], $parsed['id']);
        abort_if($tickets->isEmpty(), 404, 'Suhbat topilmadi.');
        $latest = $tickets->first();
        $user = $this->resolveCustomerUser($latest);
        [$messages, $hasMore] = $this->customerMessages($tickets, null);

        return [
            'thread' => $this->customerSummary($latest, $user),
            'messages' => $messages,
            'has_more' => $hasMore,
            'context' => $this->customerContext($latest, $tickets, $user),
        ];
    }

    public function olderMessages(string $key, int $beforeId): array
    {
        $parsed = self::parseKey($key);
        if ($parsed['segment'] === 'shop') {
            [$messages, $hasMore] = $this->shopMessages($this->shopTickets($parsed['id']), $beforeId);
        } else {
            [$messages, $hasMore] = $this->customerMessages($this->customerTickets($parsed['source'], $parsed['id']), $beforeId);
        }

        return ['messages' => $messages, 'has_more' => $hasMore];
    }

    /** @return Collection<int, BotTicket> */
    private function customerTickets(string $source, int $userId): Collection
    {
        return BotTicket::query()
            ->where('source_type', $source)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    /** @return Collection<int, SellerSupportTicket> */
    private function shopTickets(int $sellerId): Collection
    {
        return SellerSupportTicket::query()
            ->where('seller_id', $sellerId)
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    private function customerMessages(Collection $tickets, ?int $beforeId, int $limit = 80): array
    {
        $ticketUsers = $tickets->mapWithKeys(fn (BotTicket $t) => [(int) $t->id => (int) $t->user_id]);

        $rows = BotTicketMessage::query()
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit + 30)
            ->get()
            // Eski Telegram oqimi: operatorga nusxalangan xabar ikkinchi marta saqlangan
            ->reject(fn (BotTicketMessage $m) => $m->sent_by === 'user'
                && $m->telegram_actor_id
                && (int) $m->telegram_actor_id !== (int) ($ticketUsers[(int) $m->ticket_id] ?? 0))
            ->values();

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->reverse()->values();

        $attachments = BotTicketAttachment::query()->whereIn('ticket_id', $tickets->pluck('id'))->get();
        $byMessage = $attachments->whereNotNull('message_id')->groupBy('message_id');
        $loose = $attachments->whereNull('message_id');

        $this->primeAdminNames($rows->pluck('admin_id')->filter()->all());

        $items = $rows->map(function (BotTicketMessage $m) use ($byMessage, $loose) {
            $files = $byMessage->get($m->id, collect());
            if ($files->isEmpty() && $m->message_type !== 'text' && $loose->isNotEmpty()) {
                $files = $loose->filter(fn ($a) => (int) $a->ticket_id === (int) $m->ticket_id
                    && $a->file_type === $m->message_type
                    && $a->created_at && $m->created_at
                    && abs($a->created_at->diffInSeconds($m->created_at)) <= 5)->take(1);
            }

            return $this->customerMessagePayload($m, $files);
        })->values()->all();

        return [$items, $hasMore];
    }

    public function customerMessagePayload(BotTicketMessage $m, ?Collection $files = null): array
    {
        $from = $this->customerFrom($m);
        $author = null;
        if ($from === 'agent' || $from === 'note') {
            $author = $m->admin_id ? $this->adminName((int) $m->admin_id) : ($m->operator_id ? $this->operatorName((int) $m->operator_id) : 'Kitobchi');
        }

        $files ??= BotTicketAttachment::query()->where('message_id', $m->id)->get();

        return [
            'id' => 'c' . $m->id,
            'raw_id' => (int) $m->id,
            'ticket_id' => (int) $m->ticket_id,
            'from' => $from,
            'type' => $m->message_type ?: 'text',
            'text' => (string) ($m->message ?? ''),
            'author' => $author,
            'at' => optional($m->created_at)->toIso8601String(),
            'delivered' => (bool) $m->is_delivered,
            'error' => $m->delivery_error,
            'attachments' => $files->map(fn ($a) => [
                'id' => (int) $a->id,
                'type' => $a->file_type,
                'name' => $a->file_name,
                'size' => $a->file_size ? (int) $a->file_size : null,
                'url' => route('boshqaruv.support.inbox.media', ['attachment' => $a->id]),
            ])->values()->all(),
        ];
    }

    private function shopMessages(Collection $tickets, ?int $beforeId, int $limit = 80): array
    {
        $rows = SellerSupportTicketMessage::query()
            ->whereIn('ticket_id', $tickets->pluck('id'))
            ->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->reverse()->values();
        $this->primeAdminNames($rows->where('sender_type', 'admin')->pluck('sender_id')->filter()->all());

        return [$rows->map(fn (SellerSupportTicketMessage $m) => $this->shopMessagePayload($m))->values()->all(), $hasMore];
    }

    public function shopMessagePayload(SellerSupportTicketMessage $m): array
    {
        $from = $this->shopFrom($m);

        return [
            'id' => 's' . $m->id,
            'raw_id' => (int) $m->id,
            'ticket_id' => (int) $m->ticket_id,
            'from' => $from,
            'type' => $from === 'note' ? 'note' : ($from === 'system' ? 'system' : 'text'),
            'text' => (string) $m->message,
            'author' => in_array($from, ['agent', 'note'], true) && $m->sender_id ? $this->adminName((int) $m->sender_id) : null,
            'at' => optional($m->created_at)->toIso8601String(),
            'delivered' => true,
            'error' => null,
            'attachments' => [],
        ];
    }

    private function customerFrom(BotTicketMessage $m): string
    {
        if ($m->message_type === 'note') {
            return 'note';
        }

        return match ($m->sent_by) {
            'user' => 'customer',
            'operator', 'admin' => 'agent',
            default => 'system',
        };
    }

    private function shopFrom(SellerSupportTicketMessage $m): string
    {
        if ($m->is_internal) {
            return $m->sender_type === 'admin' ? 'note' : 'system';
        }

        return match ($m->sender_type) {
            'seller' => 'customer',
            'admin' => 'agent',
            default => 'system',
        };
    }

    // ─── O'NG PANEL (KONTEKST) ───────────────────────────────────────────────

    private function customerContext(BotTicket $latest, Collection $tickets, ?User $user): array
    {
        $orders = [];
        $stats = null;
        if ($user) {
            $orders = Sold::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->take(6)
                ->get()
                ->map(fn (Sold $o) => [
                    'id' => (int) $o->id,
                    'amount' => (float) ($o->amount ?? 0),
                    'status' => $this->orderStatus($o),
                    'date' => optional($o->created_at)->toIso8601String(),
                    'delivery_date' => $o->delivery_date ? Carbon::parse($o->delivery_date)->toDateString() : null,
                    'url' => route('boshqaruv.orders', ['orders_search' => $o->id, 'orders_tab' => 'all']),
                ])->all();

            $stats = [
                'orders_count' => Sold::query()->where('user_id', $user->id)->count(),
                'total_spent' => (float) Sold::query()->where('user_id', $user->id)
                    ->whereIn('status_code', ['delivered', 'customer_received'])->sum('amount'),
                'cashback' => (float) ($user->cashback ?? 0),
                'registered' => optional($user->created_at)->toDateString(),
            ];
        }

        $this->primeAdminNames($tickets->pluck('admin_id')->filter()->all());

        return [
            'kind' => 'customer',
            'profile' => [
                'name' => $user ? trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')) : ($latest->name ?: null),
                'username' => $latest->username && $latest->username !== 'empty' ? '@' . ltrim($latest->username, '@') : ($user?->username ? '@' . $user->username : null),
                'phone' => $user?->phone_number,
                'channel' => $latest->source_type === 'shop_chat' ? 'Ilova chati' : 'Telegram bot',
                'telegram_id' => $latest->source_type === 'shop_chat' ? ($user?->telegram_id ? (int) $user->telegram_id : null) : (int) $latest->user_id,
                'user_id' => $user?->id,
                'user_url' => $user ? route('boshqaruv.users', ['users_search' => $user->phone_number ?: $user->id]) : null,
                'avatar' => $this->mediaUrl($user?->avatar ?: $user?->telegram_photo),
            ],
            'stats' => $stats,
            'orders' => $orders,
            'feedback' => $this->feedbackSummary($tickets),
            'tickets' => $tickets->take(15)->map(fn (BotTicket $t) => [
                'id' => (int) $t->id,
                'status' => in_array($t->status, self::CUSTOMER_OPEN, true) ? 'open' : 'closed',
                'title' => mb_substr((string) $t->first_msg, 0, 80),
                'created_at' => optional($t->created_at)->toIso8601String(),
                'closed_at' => optional($t->closed_at)->toIso8601String(),
                'feedback' => $t->feedback,
                'agent' => $t->admin_id ? $this->adminName((int) $t->admin_id) : ($t->operator_id ? $this->operatorName((int) $t->operator_id) : null),
            ])->values()->all(),
        ];
    }

    private function shopContext(int $sellerId, Collection $tickets): array
    {
        $seller = Seller::query()->find($sellerId);
        $this->primeAdminNames($tickets->pluck('admin_id')->filter()->all());

        $ordersCount = 0;
        try {
            $ordersCount = (int) ($seller?->successful_orders ?? 0);
        } catch (\Throwable) {
        }

        return [
            'kind' => 'shop',
            'profile' => [
                'name' => $seller?->shop_name,
                'owner' => $seller ? trim(($seller->firstname ?? '') . ' ' . ($seller->lastname ?? '')) : null,
                'phone' => $seller?->phone_number,
                'status' => $seller?->status,
                'region' => $seller?->region,
                'registered' => optional($seller?->created_at)->toDateString(),
                'rating' => $seller?->rating !== null ? (float) $seller->rating : null,
                'successful_orders' => $ordersCount,
                'seller_url' => $seller ? route('boshqaruv.sellers.detail', ['seller' => $seller->id]) : null,
                'avatar' => $this->mediaUrl($seller?->photo),
            ],
            'stats' => null,
            'orders' => [],
            'feedback' => $this->feedbackSummary($tickets),
            'tickets' => $tickets->take(15)->map(fn (SellerSupportTicket $t) => [
                'id' => (int) $t->id,
                'status' => $t->status === 'closed' ? 'closed' : 'open',
                'title' => (string) ($t->subject ?: 'Murojaat #' . $t->id),
                'created_at' => optional($t->created_at)->toIso8601String(),
                'closed_at' => optional($t->closed_at)->toIso8601String(),
                'feedback' => $t->feedback,
                'agent' => $t->admin_id ? $this->adminName((int) $t->admin_id) : null,
            ])->values()->all(),
        ];
    }

    private function feedbackSummary(Collection $tickets): array
    {
        $rated = $tickets->filter(fn ($t) => in_array($t->feedback, ['good', 'bad'], true));

        return [
            'good' => $rated->where('feedback', 'good')->count(),
            'bad' => $rated->where('feedback', 'bad')->count(),
            'items' => $rated->take(8)->map(fn ($t) => [
                'ticket_id' => (int) $t->id,
                'value' => $t->feedback,
                'at' => optional($t->feedback_at)->toIso8601String(),
                'agent' => $t->admin_id ? $this->adminName((int) $t->admin_id) : null,
            ])->values()->all(),
        ];
    }

    // ─── AMALLAR ─────────────────────────────────────────────────────────────

    public function reply(string $key, Admin $me, string $text, bool $note = false): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new InvalidArgumentException('Xabar bo‘sh.');
        }

        $parsed = self::parseKey($key);

        return $parsed['segment'] === 'shop'
            ? $this->replyShop($parsed['id'], $me, $text, $note)
            : $this->replyCustomer($parsed['source'], $parsed['id'], $me, $text, $note);
    }

    private function replyCustomer(string $source, int $userId, Admin $me, string $text, bool $note): array
    {
        $ticket = BotTicket::query()->where('source_type', $source)->where('user_id', $userId)->latest('id')->first();
        abort_unless($ticket, 404, 'Suhbat topilmadi.');

        if ($note) {
            $saved = SessionService::saveMessage(
                ticketId: (int) $ticket->id,
                sentBy: 'admin',
                message: $text,
                messageType: 'note',
                adminId: $me->id,
            );

            return $this->customerMessagePayload($saved, collect());
        }

        // Yopilgan suhbatga operator yozsa — yangi murojaat ochiladi (KPI aniq bo'lishi uchun)
        if (! in_array($ticket->status, self::CUSTOMER_OPEN, true)) {
            $ticket = BotTicket::create([
                'user_id' => $ticket->user_id,
                'source_type' => $ticket->source_type,
                'source_conversation_id' => $ticket->source_conversation_id,
                'username' => $ticket->username,
                'name' => $ticket->name,
                'status' => SessionService::STATUS_ACTIVE,
                'first_msg' => mb_substr($text, 0, 1000),
                'admin_id' => $me->id,
                'last_message_at' => now(),
            ]);
            SessionService::saveSystemMessage((int) $ticket->id, 'Operator yangi suhbat boshladi: ' . $me->name);
        } else {
            $ticket->update([
                'status' => SessionService::STATUS_ACTIVE,
                'admin_id' => $ticket->admin_id ?: $me->id,
            ]);
        }

        if ($ticket->source_type === 'shop_chat') {
            app(SupportChatBridgeService::class)->sendReplyToConversation($ticket, $text, null, $me->id);
            $saved = BotTicketMessage::query()->where('ticket_id', $ticket->id)->where('sent_by', 'admin')->latest('id')->first();

            return $saved ? $this->customerMessagePayload($saved, collect()) : [];
        }

        $telegramMessageId = null;
        $error = null;
        try {
            $response = app(TelegramSupportService::class)->sendText((int) $ticket->user_id, $text, null);
            $telegramMessageId = (int) data_get($response, 'result.message_id') ?: null;
        } catch (\Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 500);
            Log::warning('[SupportInbox] Telegram javobi yuborilmadi', ['ticket_id' => $ticket->id, 'error' => $error]);
        }

        $saved = SessionService::saveMessage(
            ticketId: (int) $ticket->id,
            sentBy: 'admin',
            message: $text,
            messageType: 'text',
            adminId: $me->id,
            telegramMessageId: $telegramMessageId,
            isDelivered: $error === null,
            deliveryError: $error,
        );

        return $this->customerMessagePayload($saved, collect());
    }

    private function replyShop(int $sellerId, Admin $me, string $text, bool $note): array
    {
        $ticket = SellerSupportTicket::query()->where('seller_id', $sellerId)->where('status', '!=', 'closed')->latest('id')->first();

        if (! $ticket && $note) {
            $ticket = SellerSupportTicket::query()->where('seller_id', $sellerId)->latest('id')->first();
        }

        if (! $ticket) {
            abort_unless(Seller::query()->whereKey($sellerId)->exists(), 404, 'Do‘kon topilmadi.');
            $ticket = SellerSupportTicket::create([
                'seller_id' => $sellerId,
                'admin_id' => $me->id,
                'subject' => 'Kitobchi qo‘llab-quvvatlash',
                'status' => 'answered',
                'last_message_at' => now(),
            ]);
        }

        $message = SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $me->id,
            'message' => $text,
            'is_internal' => $note,
        ]);

        if (! $note) {
            $ticket->forceFill([
                'status' => 'answered',
                'admin_id' => $ticket->admin_id ?: $me->id,
                'last_message_at' => now(),
                'seller_unread_count' => (int) $ticket->seller_unread_count + 1,
                'first_response_at' => $ticket->first_response_at ?: now(),
            ])->save();

            $this->notifySeller($ticket, $message);

            try {
                SendSellerSupportPush::dispatch((int) $ticket->id, 'reply', mb_substr($text, 0, 140))->delay(now()->addSeconds(10));
            } catch (\Throwable $e) {
                Log::warning('[SupportInbox] seller push navbatga qo‘yilmadi: ' . $e->getMessage());
            }
        }

        $this->afterSellerMessage($message, false);

        return $this->shopMessagePayload($message);
    }

    public function assign(string $key, Admin $me, ?int $adminId): array
    {
        $parsed = self::parseKey($key);
        $target = $adminId ? Admin::query()->whereKey($adminId)->first() : null;
        abort_if($adminId && ! $target, 422, 'Operator topilmadi.');

        $label = $target ? 'Suhbat biriktirildi: ' . $target->name : 'Suhbat operatordan bo‘shatildi';
        $label .= ' (' . $me->name . ')';

        if ($parsed['segment'] === 'shop') {
            $ticket = SellerSupportTicket::query()->where('seller_id', $parsed['id'])->latest('id')->firstOrFail();
            $ticket->update(['admin_id' => $target?->id]);
            $msg = SellerSupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'system',
                'sender_id' => null,
                'message' => $label,
                'is_internal' => true,
            ]);
            $this->afterSellerMessage($msg, false);

            return $this->shopSummary($ticket->fresh());
        }

        $ticket = BotTicket::query()->where('source_type', $parsed['source'])->where('user_id', $parsed['id'])->latest('id')->firstOrFail();
        $updates = ['admin_id' => $target?->id];
        if ($target && $ticket->status === SessionService::STATUS_QUEUE) {
            $updates['status'] = SessionService::STATUS_ACTIVE;
        }
        $ticket->update($updates);
        SessionService::saveSystemMessage((int) $ticket->id, $label);

        return $this->customerSummary($ticket->fresh(), null, true);
    }

    public function close(string $key, Admin $me): array
    {
        $parsed = self::parseKey($key);

        if ($parsed['segment'] === 'shop') {
            $tickets = SellerSupportTicket::query()->where('seller_id', $parsed['id'])->where('status', '!=', 'closed')->get();
            foreach ($tickets as $ticket) {
                $ticket->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'close_reason' => 'panel_closed',
                    'admin_id' => $ticket->admin_id ?: $me->id,
                ]);
                $msg = SellerSupportTicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'sender_type' => 'system',
                    'sender_id' => null,
                    'message' => 'Murojaat yakunlandi. Yordamimiz foydali bo‘ldimi? Iltimos, baholang.',
                    'is_internal' => false,
                ]);
                $this->notifySeller($ticket->fresh(), $msg);
                try {
                    SendSellerSupportPush::dispatch((int) $ticket->id, 'closed', 'Murojaatingiz yakunlandi. Yordamimizni baholang.');
                } catch (\Throwable $e) {
                    Log::warning('[SupportInbox] seller close push: ' . $e->getMessage());
                }
            }

            $latest = SellerSupportTicket::query()->where('seller_id', $parsed['id'])->latest('id')->firstOrFail();
            $this->broadcastThread($this->shopSummary($latest));

            return $this->shopSummary($latest);
        }

        $ticket = BotTicket::query()->where('source_type', $parsed['source'])->where('user_id', $parsed['id'])->latest('id')->firstOrFail();
        if (in_array($ticket->status, self::CUSTOMER_OPEN, true)) {
            if (! $ticket->admin_id) {
                $ticket->update(['admin_id' => $me->id]);
            }
            SessionService::saveSystemMessage((int) $ticket->id, 'Suhbat yopildi: ' . $me->name);
            SessionService::closeTicket((int) $ticket->id, 'panel_closed');
            $ticket->forceFill(['feedback_requested_at' => now()])->save();

            if ($ticket->source_type !== 'shop_chat') {
                $this->requestTelegramFeedback($ticket);
            }
        }

        $summary = $this->customerSummary($ticket->fresh(), null, true);
        $this->broadcastThread($summary);

        return $summary;
    }

    public function markRead(string $key): void
    {
        $parsed = self::parseKey($key);

        if ($parsed['segment'] === 'shop') {
            $changed = SellerSupportTicket::query()->where('seller_id', $parsed['id'])->where('admin_unread_count', '>', 0)->update(['admin_unread_count' => 0]);
            if ($changed) {
                $latest = SellerSupportTicket::query()->where('seller_id', $parsed['id'])->latest('id')->first();
                $latest && $this->broadcastThread($this->shopSummary($latest));
            }

            return;
        }

        $changed = BotTicket::query()->where('source_type', $parsed['source'])->where('user_id', $parsed['id'])
            ->where('admin_unread_count', '>', 0)->update(['admin_unread_count' => 0]);
        if ($changed) {
            $latest = BotTicket::query()->where('source_type', $parsed['source'])->where('user_id', $parsed['id'])->latest('id')->first();
            $latest && $this->broadcastThread($this->customerSummary($latest, null, true));
        }
    }

    // ─── BAHO (yaxshi / yomon) ───────────────────────────────────────────────

    public function recordCustomerFeedback(BotTicket $ticket, string $value): bool
    {
        if (! in_array($value, ['good', 'bad'], true) || $ticket->feedback) {
            return false;
        }

        $ticket->forceFill([
            'feedback' => $value,
            'feedback_at' => now(),
            'status' => in_array($ticket->status, self::CUSTOMER_OPEN, true) ? $ticket->status : SessionService::STATUS_RATED,
        ])->save();

        SessionService::saveSystemMessage((int) $ticket->id, $value === 'good' ? 'Mijoz bahosi: yaxshi' : 'Mijoz bahosi: yomon');

        return true;
    }

    public function recordShopFeedback(SellerSupportTicket $ticket, string $value): bool
    {
        if (! in_array($value, ['good', 'bad'], true) || $ticket->feedback) {
            return false;
        }

        $ticket->forceFill(['feedback' => $value, 'feedback_at' => now()])->save();
        $msg = SellerSupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'system',
            'sender_id' => null,
            'message' => $value === 'good' ? 'Do‘kon bahosi: yaxshi' : 'Do‘kon bahosi: yomon',
            'is_internal' => true,
        ]);
        $this->afterSellerMessage($msg, false);

        return true;
    }

    private function requestTelegramFeedback(BotTicket $ticket): void
    {
        try {
            app(TelegramSupportService::class)->sendFeedbackRequest((int) $ticket->user_id, (int) $ticket->id);
        } catch (\Throwable $e) {
            Log::info('[SupportInbox] Telegram baho so‘rovi yuborilmadi: ' . $e->getMessage());
        }
    }

    // ─── HOOKLAR VA REAL-VAQT ────────────────────────────────────────────────

    /**
     * SessionService::saveMessage dan keyin chaqiriladi.
     */
    public function afterTicketMessage(BotTicketMessage $message): void
    {
        $ticket = BotTicket::query()->find($message->ticket_id);
        if (! $ticket) {
            return;
        }

        $updates = ['last_message_at' => $message->created_at ?? now()];
        if ($message->sent_by === 'user') {
            $updates['admin_unread_count'] = (int) $ticket->admin_unread_count + 1;
        }
        if (in_array($message->sent_by, ['admin', 'operator'], true)
            && $message->message_type !== 'note'
            && ! $ticket->first_response_at) {
            $updates['first_response_at'] = $message->created_at ?? now();
        }
        $ticket->forceFill($updates)->saveQuietly();

        $key = self::customerKey($ticket);
        $payload = $this->customerMessagePayload($message);
        $summary = $this->customerSummary($ticket->fresh(), null, true);

        $this->safeBroadcast(new SupportInboxUpdated('message', 'customer', $key, $summary, $payload));
    }

    /**
     * Seller tiketiga yangi xabar (do'kon yoki operator) — panelga yuboriladi.
     */
    public function afterSellerMessage(SellerSupportTicketMessage $message, bool $fromSeller): void
    {
        $ticket = SellerSupportTicket::query()->find($message->ticket_id);
        if (! $ticket) {
            return;
        }

        $this->safeBroadcast(new SupportInboxUpdated(
            'message',
            'shop',
            self::shopKey((int) $ticket->seller_id),
            $this->shopSummary($ticket),
            $this->shopMessagePayload($message),
        ));
    }

    public function broadcastThread(array $summary): void
    {
        $this->safeBroadcast(new SupportInboxUpdated('thread', $summary['segment'], $summary['key'], $summary, null));
    }

    public function notifySeller(SellerSupportTicket $ticket, ?SellerSupportTicketMessage $message = null): void
    {
        $this->safeBroadcast(new SellerSupportTicketUpdated(
            (int) $ticket->seller_id,
            self::sellerTicketPayload($ticket),
            $message && ! $message->is_internal ? self::sellerMessagePayload($message) : null,
        ));
    }

    public static function sellerTicketPayload(SellerSupportTicket $ticket): array
    {
        return [
            'id' => (int) $ticket->id,
            'subject' => $ticket->subject ?: 'Kitobchi bilan suhbat',
            'status' => $ticket->status,
            'unread_count' => (int) $ticket->seller_unread_count,
            'feedback' => $ticket->feedback,
            'can_rate' => $ticket->status === 'closed' && ! $ticket->feedback,
            'last_message_at' => optional($ticket->last_message_at ?: $ticket->updated_at)->format('d.m.Y H:i'),
        ];
    }

    public static function sellerMessagePayload(SellerSupportTicketMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'ticket_id' => (int) $message->ticket_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_type === 'admin'
                ? ($message->admin?->name ?: 'Kitobchi support')
                : ($message->sender_type === 'seller' ? 'Siz' : 'Tizim'),
            'message' => $message->message,
            'created_at' => optional($message->created_at)->format('d.m.Y H:i'),
        ];
    }

    private function safeBroadcast(object $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('[SupportInbox] broadcast xatosi: ' . $e->getMessage());
        }
    }

    // ─── KPI ─────────────────────────────────────────────────────────────────

    public function kpi(Carbon $from, Carbon $to): array
    {
        $customer = BotTicket::query()->whereBetween('created_at', [$from, $to]);
        $shop = SellerSupportTicket::query()->whereBetween('created_at', [$from, $to]);

        $frt = function (string $table) use ($from, $to) {
            return DB::table($table)
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('first_response_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) AS avg_s')
                ->selectRaw('COUNT(*) AS n')
                ->first();
        };
        $frtC = $frt('bot_tickets');
        $frtS = $frt('seller_support_tickets');
        $frtN = (int) $frtC->n + (int) $frtS->n;
        $frtAvg = $frtN > 0 ? (((float) $frtC->avg_s * (int) $frtC->n) + ((float) $frtS->avg_s * (int) $frtS->n)) / $frtN : null;

        $good = BotTicket::query()->whereBetween('feedback_at', [$from, $to])->where('feedback', 'good')->count()
            + SellerSupportTicket::query()->whereBetween('feedback_at', [$from, $to])->where('feedback', 'good')->count();
        $bad = BotTicket::query()->whereBetween('feedback_at', [$from, $to])->where('feedback', 'bad')->count()
            + SellerSupportTicket::query()->whereBetween('feedback_at', [$from, $to])->where('feedback', 'bad')->count();

        $totals = [
            'new' => (clone $customer)->count() + (clone $shop)->count(),
            'new_customer' => (clone $customer)->count(),
            'new_shop' => (clone $shop)->count(),
            'closed' => BotTicket::query()->whereBetween('closed_at', [$from, $to])->count()
                + SellerSupportTicket::query()->whereBetween('closed_at', [$from, $to])->count(),
            'open_now' => BotTicket::query()->whereIn('status', self::CUSTOMER_OPEN)->count()
                + SellerSupportTicket::query()->where('status', '!=', 'closed')->count(),
            'unassigned_now' => BotTicket::query()->whereIn('status', self::CUSTOMER_OPEN)->whereNull('admin_id')
                ->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', 0))->count()
                + SellerSupportTicket::query()->where('status', '!=', 'closed')->whereNull('admin_id')->count(),
            'avg_first_response_s' => $frtAvg !== null ? (int) round($frtAvg) : null,
            'good' => $good,
            'bad' => $bad,
            'satisfaction' => ($good + $bad) > 0 ? (int) round($good * 100 / ($good + $bad)) : null,
        ];

        $agents = $this->supportAdmins()->map(function (Admin $admin) use ($from, $to) {
            $handled = BotTicket::query()->where('admin_id', $admin->id)->whereBetween('created_at', [$from, $to])->count()
                + SellerSupportTicket::query()->where('admin_id', $admin->id)->whereBetween('created_at', [$from, $to])->count();
            $closed = BotTicket::query()->where('admin_id', $admin->id)->whereBetween('closed_at', [$from, $to])->count()
                + SellerSupportTicket::query()->where('admin_id', $admin->id)->whereBetween('closed_at', [$from, $to])->count();
            $replies = BotTicketMessage::query()->where('admin_id', $admin->id)->where('sent_by', 'admin')->where('message_type', '!=', 'note')
                ->whereBetween('created_at', [$from, $to])->count()
                + SellerSupportTicketMessage::query()->where('sender_type', 'admin')->where('sender_id', $admin->id)->where('is_internal', false)
                    ->whereBetween('created_at', [$from, $to])->count();
            $good = BotTicket::query()->where('admin_id', $admin->id)->where('feedback', 'good')->whereBetween('feedback_at', [$from, $to])->count()
                + SellerSupportTicket::query()->where('admin_id', $admin->id)->where('feedback', 'good')->whereBetween('feedback_at', [$from, $to])->count();
            $bad = BotTicket::query()->where('admin_id', $admin->id)->where('feedback', 'bad')->whereBetween('feedback_at', [$from, $to])->count()
                + SellerSupportTicket::query()->where('admin_id', $admin->id)->where('feedback', 'bad')->whereBetween('feedback_at', [$from, $to])->count();

            $frtRows = collect([
                DB::table('bot_tickets')->where('admin_id', $admin->id)->whereBetween('created_at', [$from, $to])->whereNotNull('first_response_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) AS avg_s, COUNT(*) AS n')->first(),
                DB::table('seller_support_tickets')->where('admin_id', $admin->id)->whereBetween('created_at', [$from, $to])->whereNotNull('first_response_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) AS avg_s, COUNT(*) AS n')->first(),
            ]);
            $n = (int) $frtRows->sum(fn ($r) => (int) $r->n);
            $avg = $n > 0 ? $frtRows->sum(fn ($r) => (float) $r->avg_s * (int) $r->n) / $n : null;

            return [
                'id' => (int) $admin->id,
                'name' => $admin->name,
                'handled' => $handled,
                'closed' => $closed,
                'replies' => $replies,
                'good' => $good,
                'bad' => $bad,
                'satisfaction' => ($good + $bad) > 0 ? (int) round($good * 100 / ($good + $bad)) : null,
                'avg_first_response_s' => $avg !== null ? (int) round($avg) : null,
                'open_now' => BotTicket::query()->where('admin_id', $admin->id)->whereIn('status', self::CUSTOMER_OPEN)->count()
                    + SellerSupportTicket::query()->where('admin_id', $admin->id)->where('status', '!=', 'closed')->count(),
            ];
        })->sortByDesc('handled')->values()->all();

        $badCustomer = BotTicket::query()->where('feedback', 'bad')->whereBetween('feedback_at', [$from, $to])->latest('feedback_at')->take(15)->get()
            ->map(fn (BotTicket $t) => [
                'key' => self::customerKey($t),
                'segment' => 'customer',
                'ticket_id' => (int) $t->id,
                'name' => $t->name ?: ('Mijoz #' . $t->user_id),
                'agent' => $t->admin_id ? $this->adminName((int) $t->admin_id) : null,
                'at' => optional($t->feedback_at)->toIso8601String(),
                'title' => mb_substr((string) $t->first_msg, 0, 90),
            ]);
        $badShop = SellerSupportTicket::query()->with('seller')->where('feedback', 'bad')->whereBetween('feedback_at', [$from, $to])->latest('feedback_at')->take(15)->get()
            ->map(fn (SellerSupportTicket $t) => [
                'key' => self::shopKey((int) $t->seller_id),
                'segment' => 'shop',
                'ticket_id' => (int) $t->id,
                'name' => $t->seller?->shop_name ?: ('Do‘kon #' . $t->seller_id),
                'agent' => $t->admin_id ? $this->adminName((int) $t->admin_id) : null,
                'at' => optional($t->feedback_at)->toIso8601String(),
                'title' => (string) $t->subject,
            ]);

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lte($end) && $guard++ < 92) {
            $dayStart = $cursor->copy();
            $dayEnd = $cursor->copy()->endOfDay();
            $days[] = [
                'date' => $dayStart->toDateString(),
                'new' => BotTicket::query()->whereBetween('created_at', [$dayStart, $dayEnd])->count()
                    + SellerSupportTicket::query()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'closed' => BotTicket::query()->whereBetween('closed_at', [$dayStart, $dayEnd])->count()
                    + SellerSupportTicket::query()->whereBetween('closed_at', [$dayStart, $dayEnd])->count(),
            ];
            $cursor->addDay();
        }

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => $totals,
            'agents' => $agents,
            'bad_feedback' => $badCustomer->concat($badShop)->sortByDesc('at')->values()->take(20)->all(),
            'days' => $days,
        ];
    }

    // ─── YORDAMCHILAR ────────────────────────────────────────────────────────

    /** @return Collection<int, Admin> */
    public function supportAdmins(): Collection
    {
        return Admin::query()
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', true))
            ->orderBy('name')
            ->get()
            ->filter(fn (Admin $admin) => $admin->hasPermission('support') && ! ($admin->is_read_only ?? false))
            ->values();
    }

    public function resolveCustomerUser(BotTicket $ticket): ?User
    {
        if ($ticket->source_type === 'shop_chat') {
            return User::query()->find($ticket->user_id);
        }

        return User::query()->where('telegram_id', $ticket->user_id)->first();
    }

    private function primeAdminNames(array $ids): void
    {
        $missing = array_values(array_diff(array_unique(array_map('intval', $ids)), array_keys($this->adminNames)));
        if ($missing === []) {
            return;
        }
        foreach (Admin::query()->whereIn('id', $missing)->get(['id', 'name']) as $admin) {
            $this->adminNames[(int) $admin->id] = (string) $admin->name;
        }
    }

    private function adminName(int $id): string
    {
        if (! array_key_exists($id, $this->adminNames)) {
            $this->primeAdminNames([$id]);
        }

        return $this->adminNames[$id] ?? ('Operator #' . $id);
    }

    private function operatorName(int $telegramId): string
    {
        $op = BotOperator::query()->where('telegram_id', $telegramId)->first();

        return $op?->name ?: ($op?->username ? '@' . $op->username : 'Telegram operator');
    }

    private function preview(?string $text, ?string $type): string
    {
        $text = trim(strip_tags((string) $text));
        $prefix = match ($type) {
            'note' => 'Eslatma: ',
            'photo' => $text === '' || str_starts_with($text, '[') ? 'Rasm' : '',
            'voice' => 'Ovozli xabar',
            'video', 'video_note' => 'Video',
            'document' => 'Hujjat',
            default => '',
        };
        if (in_array($type, ['voice', 'video', 'video_note', 'document'], true) && str_starts_with($text, '[')) {
            return $prefix;
        }
        if ($type === 'photo' && $prefix !== '') {
            return $prefix;
        }

        return mb_substr($prefix . preg_replace('/\s+/u', ' ', $text), 0, 120);
    }

    private function orderStatus(Sold $order): string
    {
        $value = $order->status_code instanceof \BackedEnum ? $order->status_code->value : ($order->status_code ?: $order->status);

        return (string) $value;
    }

    private function mediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
