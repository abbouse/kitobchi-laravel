<?php

namespace App\Services\TeamBot;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Books;
use App\Models\Gifts;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\TeamBotPost;
use App\Models\TeamTask;
use App\Services\AdminOrderStatusSyncService;
use App\Services\OrderService;
use App\Services\OrderStatusPushService;
use App\Support\AdminOrderStatusPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Jamoa boti: guruhga yangi buyurtmalarni yozadi, tugmalar orqali buyurtmani
 * "o'ziga oladi" / keyingi bosqichga o'tkazadi, jamoa vazifalarini yuritadi.
 *
 * Xavfsizlik: tugmani bosgan odam boshqaruvdagi admin akkauntiga bog'langan
 * bo'lishi shart; buyurtma amallari — "Buyurtmalar" huquqi bor va faqat-ko'rish
 * rejimida bo'lmagan adminga. Har bir amal audit logga yoziladi.
 */
class TeamBotService
{
    /** Buyurtmaning keyingi odatiy bosqichi (bot tugmasi) */
    public const NEXT = [
        'pending' => ['packing', '📦 Qadoqlashga'],
        'packing' => ['in_delivery', '🚚 Yetkazishga berildi'],
        'in_delivery' => ['delivered', '✅ Yetib bordi'],
    ];

    public function __construct(private readonly TeamBotClient $tg)
    {
    }

    public function enabled(): bool
    {
        return $this->tg->enabled() && $this->chatId() !== null;
    }

    // ─── SOZLAMALAR ──────────────────────────────────────────────────────────

    public function setting(string $key, ?string $default = null): ?string
    {
        $value = DB::table('team_bot_settings')->where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public function putSetting(string $key, ?string $value): void
    {
        DB::table('team_bot_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
    }

    public function chatId(): ?int
    {
        $id = $this->setting('chat_id', (string) config('services.team_bot.chat_id', ''));

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    public function threadId(string $topic): ?int
    {
        $id = $this->setting("thread_{$topic}", (string) config("services.team_bot.threads.{$topic}", ''));

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    // ─── ADMIN BOG'LASH ──────────────────────────────────────────────────────

    public function linkUrl(Admin $admin): ?string
    {
        $username = $this->tg->username();
        if (! $username) {
            return null;
        }
        $token = Str::random(32);
        $admin->forceFill(['telegram_link_token' => $token, 'telegram_link_expires_at' => now()->addMinutes(15)])->save();

        return "https://t.me/{$username}?start=link_{$token}";
    }

    public function linkByToken(string $token, array $from): ?Admin
    {
        $admin = Admin::query()
            ->where('telegram_link_token', $token)
            ->where('telegram_link_expires_at', '>', now())
            ->where('is_active', true)
            ->first();
        if (! $admin) {
            return null;
        }
        DB::transaction(function () use ($admin, $from) {
            Admin::query()->where('telegram_user_id', (int) $from['id'])->where('id', '!=', $admin->id)
                ->update(['telegram_user_id' => null, 'telegram_username' => null]);
            $admin->forceFill([
                'telegram_user_id' => (int) $from['id'],
                'telegram_username' => $from['username'] ?? null,
                'telegram_link_token' => null,
                'telegram_link_expires_at' => null,
            ])->save();
        });

        return $admin;
    }

    public function adminFor(array $from): ?Admin
    {
        $admin = Admin::query()->where('telegram_user_id', (int) ($from['id'] ?? 0))->where('is_active', true)->first();
        // Username o'zgargan bo'lsa yangilab qo'yamiz (@mention bilan vazifa berish uchun)
        if ($admin && ($from['username'] ?? null) && $admin->telegram_username !== $from['username']) {
            $admin->forceFill(['telegram_username' => $from['username']])->save();
        }

        return $admin;
    }

    public function canManageOrders(?Admin $admin): bool
    {
        return $admin !== null && ! ($admin->is_read_only ?? false) && $admin->hasPermission('orders');
    }

    public static function mention(?Admin $admin): string
    {
        if (! $admin) {
            return '—';
        }
        $name = e($admin->name);

        return $admin->telegram_user_id ? "<a href=\"tg://user?id={$admin->telegram_user_id}\">{$name}</a>" : $name;
    }

    // ─── BUYURTMA XABARI ─────────────────────────────────────────────────────
    //
    // Qoida:
    //  • Karta — guruhga faqat to'lov o'tgach (held/paid) yuboriladi. To'lanmagan
    //    karta buyurtmasi jamoani chalg'itmaydi.
    //  • Naqd — darhol yuboriladi: jamoadan kimdir mijozga qo'ng'iroq qilib
    //    tasdiqlaydi (yoki mijoz rad etsa — bekor qiladi). Tasdiqlanmaguncha
    //    "Qadoqlashga" tugmasi chiqmaydi.
    //  • Xabar buyurtma bilan sinxron: holat/to'lov qayerda o'zgarsa ham
    //    (panel, ilova, kuryer, avtomatik bekor qilish) xabar yangilanadi.

    /** Guruhga chiqariladigan buyurtmalar faqat so'nggi 2 kundagilar (eski buyurtma o'zgarsa post ochilmaydi) */
    public const POST_MAX_AGE_HOURS = 48;

    public static function paymentCode(Sold $order): PaymentStatusCode
    {
        return PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
    }

    public static function statusCode(Sold $order): string
    {
        return OrderStatusCode::fromLegacy($order->status_code ?? $order->status)->value;
    }

    /** Hali post ochilmagan buyurtma guruhga chiqishi kerakmi */
    public function shouldPost(Sold $order): bool
    {
        if ($order->is_instore) {
            return false;
        }
        if ($order->created_at && $order->created_at->lt(now()->subHours(self::POST_MAX_AGE_HOURS))) {
            return false;
        }
        if (in_array(self::statusCode($order), ['cancelled', 'returned', 'delivered', 'customer_received'], true)) {
            return false;
        }

        return match (self::paymentCode($order)) {
            PaymentStatusCode::CASH_PENDING, PaymentStatusCode::HELD, PaymentStatusCode::PAID => true,
            default => false, // karta kutilmoqda yoki bekor — chiqmaydi
        };
    }

    /** Buyurtma oqimi: post ochilganda qayd etiladi (naqd keyin "paid" bo'lib ketadi) */
    private function flowOf(Sold $order, ?TeamBotPost $post): string
    {
        $saved = $post?->metaValue('flow');
        if ($saved) {
            return (string) $saved;
        }

        return self::paymentCode($order) === PaymentStatusCode::CASH_PENDING ? 'cash' : 'card';
    }

    /** Qo'ng'iroq uchun: qabul qiluvchi (manzildagi) va akkaunt egasi */
    private function contactOf(Sold $order): array
    {
        $address = (array) (collect($order->address ?? [])->first() ?? []);
        $recipientName = trim((string) ($address['fullName'] ?? $address['full_name'] ?? ''));
        $recipientPhone = trim((string) ($address['phoneNumber'] ?? $address['phone'] ?? ''));
        $accountName = trim(((string) ($order->user?->name ?? '')).' '.((string) ($order->user?->lastname ?? '')));
        $accountPhone = trim((string) ($order->user?->phone_number ?? ''));

        return [
            'name' => $recipientName !== '' ? $recipientName : ($accountName !== '' ? $accountName : 'Mijoz'),
            'phone' => $recipientPhone !== '' ? $recipientPhone : $accountPhone,
            'accountPhone' => $accountPhone !== '' && $accountPhone !== $recipientPhone ? $accountPhone : null,
            'address' => $address,
        ];
    }

    private static function maskPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return strlen($digits) >= 4 ? '••• '.substr($digits, -4, 2).' '.substr($digits, -2) : null;
    }

    private function itemsOf(Sold $order): \Illuminate\Support\Collection
    {
        return collect($order->items ?? [])->map(function ($item) {
            $item = (array) $item;
            $type = $item['type'] ?? 'book';
            $id = (int) ($item['item_id'] ?? $item['product_id'] ?? $item['productId'] ?? $item['id'] ?? 0);
            $name = trim((string) ($item['name'] ?? $item['title'] ?? ''));
            if ($name === '' && $id) {
                try {
                    $name = (string) match ($type) {
                        'stationery' => Stationery::query()->whereKey($id)->value('name'),
                        'gift' => Gifts::query()->whereKey($id)->value('name'),
                        default => Books::query()->whereKey($id)->value('name'),
                    };
                } catch (\Throwable) {
                    $name = '';
                }
            }

            return [
                'name' => $name !== '' ? $name : 'Mahsulot',
                'qty' => max(1, (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1)),
            ];
        });
    }

    private static function hm(?string $iso): string
    {
        return $iso ? Carbon::parse($iso)->timezone('Asia/Tashkent')->format('H:i') : '';
    }

    /** @return array{0:string,1:array} [matn, inline klaviatura] */
    public function renderOrder(Sold $order, ?TeamBotPost $post = null): array
    {
        $order->loadMissing('user:id,name,lastname,phone_number');
        $status = self::statusCode($order);
        $payment = self::paymentCode($order);
        $flow = $this->flowOf($order, $post);
        $final = in_array($status, ['delivered', 'customer_received', 'cancelled', 'returned'], true);
        $cancelled = in_array($status, ['cancelled', 'returned'], true) || $payment === PaymentStatusCode::CANCELLED;

        $callStatus = (string) $post?->metaValue('call_status', '');
        $confirmed = $flow !== 'cash' || $callStatus === 'confirmed' || ! in_array($status, ['pending'], true);
        $callerId = $post?->metaValue('caller');
        $caller = $callerId ? Admin::find($callerId) : null;
        $claimedId = $post?->metaValue('claimed_by');
        $claimed = $claimedId ? Admin::find($claimedId) : null;

        $head = match (true) {
            $cancelled => '❌ <b>Bekor qilingan</b>',
            in_array($status, ['delivered', 'customer_received'], true) => '✅ <b>Yetkazilgan</b>',
            $flow === 'cash' && ! $confirmed => '💵 <b>Yangi buyurtma · naqd</b> — mijozga qo‘ng‘iroq qilib tasdiqlang',
            $flow === 'cash' => '💵 <b>Buyurtma · naqd</b>',
            $payment === PaymentStatusCode::HELD => '💳 <b>Yangi buyurtma · karta</b> (summa ushlab turilgan)',
            $status === 'pending' => '💳 <b>Yangi buyurtma · to‘langan</b>',
            default => '💳 <b>Buyurtma · to‘langan</b>',
        };

        $contact = $this->contactOf($order);
        $items = $this->itemsOf($order);
        $units = $items->sum('qty');
        $address = $contact['address'];
        $place = collect([$address['region'] ?? $address['province'] ?? null, $address['district'] ?? null])->filter()->implode(', ');
        $delivery = match (Sold::normalizeDeliveryTypeValue($order->deliveryType)) {
            'pickup' => "🏬 Do'kondan olib ketadi",
            'postal' => '📮 Pochta orqali',
            default => '🛵 Kuryer',
        };
        $window = class_exists(\App\Support\DeliverySchedule::class) ? \App\Support\DeliverySchedule::windowLabel($order) : null;
        $masked = self::maskPhone((string) $contact['phone']);

        $lines = [];
        $lines[] = "{$head}\n#{$order->id}";
        $lines[] = '';
        $lines[] = '👤 '.e($contact['name']).($masked ? ' · <code>'.e($masked).'</code>' : '');
        foreach ($items->take(5) as $it) {
            $lines[] = '   • '.e(Str::limit($it['name'], 48)).($it['qty'] > 1 ? " × {$it['qty']}" : '');
        }
        if ($items->count() > 5) {
            $lines[] = '   • … yana '.($items->count() - 5).' ta';
        }
        $lines[] = '💰 <b>'.number_format((float) ($order->amount ?? 0), 0, '.', ' ')." so'm</b> · {$units} dona · ".e(AdminOrderStatusPresenter::payment($payment->value));
        $lines[] = $delivery.($window ? ' · '.e($window) : '').($place !== '' ? "\n📍 ".e($place) : '');
        if (trim((string) $order->buyerWish) !== '') {
            $lines[] = '💬 <i>'.e(Str::limit(trim((string) $order->buyerWish), 140)).'</i>';
        }
        $lines[] = '';
        $lines[] = '🏷 Holat: <b>'.e(AdminOrderStatusPresenter::mainOrder($order->status_code ?? $order->status)).'</b>';

        if ($flow === 'cash') {
            $attempts = (int) $post?->metaValue('call_attempts', 0);
            if ($callStatus === 'confirmed') {
                $by = Admin::find($post?->metaValue('confirmed_by'));
                $lines[] = '📞 Tasdiqlandi: '.self::mention($by).' · '.self::hm($post?->metaValue('confirmed_at'));
            } elseif ($callStatus === 'refused') {
                $by = Admin::find($post?->metaValue('cancelled_by'));
                $lines[] = '📞 Mijoz rad etdi: '.self::mention($by).' · '.self::hm($post?->metaValue('cancelled_at'));
            } elseif (! $cancelled) {
                $lines[] = '📞 Qo‘ng‘iroq: '.($caller ? self::mention($caller).' qo‘ng‘iroq qilyapti' : '<i>hali hech kim</i>')
                    .($attempts ? " · 📵 {$attempts} marta javob bermadi (".self::hm($post?->metaValue('last_attempt_at')).')' : '');
            }
        }
        if (! $cancelled) {
            $lines[] = "🙋 Mas'ul: ".($claimed ? self::mention($claimed) : '<i>hali hech kim</i>');
        }
        $lines[] = '🕒 '.optional($order->created_at)->timezone('Asia/Tashkent')?->format('d.m H:i');

        $rows = [];
        if (! $final && ! $cancelled) {
            if ($flow === 'cash' && ! $confirmed) {
                if ($post?->metaValue('reject_pending')) {
                    $rows[] = [
                        ['text' => '❗ Ha, bekor qilinsin', 'callback_data' => "o:{$order->id}:rejyes"],
                        ['text' => '↩️ Yo‘q', 'callback_data' => "o:{$order->id}:rejno"],
                    ];
                } elseif (! $caller) {
                    $rows[] = [['text' => '📞 Men qo‘ng‘iroq qilaman', 'callback_data' => "o:{$order->id}:call"]];
                } else {
                    $rows[] = [
                        ['text' => '✅ Tasdiqladi', 'callback_data' => "o:{$order->id}:ok"],
                        ['text' => '📵 Javob bermadi', 'callback_data' => "o:{$order->id}:noans"],
                    ];
                    $rows[] = [
                        ['text' => '📞 Raqam', 'callback_data' => "o:{$order->id}:phone"],
                        ['text' => '🚫 Mijoz rad etdi', 'callback_data' => "o:{$order->id}:rej"],
                    ];
                }
            } else {
                $row = [];
                if (! $claimed) {
                    $row[] = ['text' => '🙋 Men olaman', 'callback_data' => "o:{$order->id}:claim"];
                }
                if (isset(self::NEXT[$status])) {
                    [$next, $label] = self::NEXT[$status];
                    $row[] = ['text' => $label, 'callback_data' => "o:{$order->id}:st:{$next}"];
                }
                if ($row) {
                    $rows[] = $row;
                }
            }
            $rows[] = [['text' => '📝 Vazifa qilish', 'callback_data' => "o:{$order->id}:task"]];
        }
        $url = $this->panelUrl('boshqaruv.orders', ['orders_search' => $order->id, 'orders_tab' => 'all']);
        if ($url) {
            $rows[] = [['text' => '🔗 Boshqaruvda ochish', 'url' => $url]];
        }

        return [implode("\n", $lines), $rows];
    }

    /**
     * Buyurtma xabarini yuboradi yoki tahrirlaydi.
     * $create=true — post hali yo'q bo'lsa va qoida ruxsat bersa ochiladi
     * (karta: to'langach; naqd: darhol). Aks holda faqat mavjud post yangilanadi.
     */
    public function syncOrder(int $orderId, bool $create): void
    {
        if (! $this->enabled()) {
            return;
        }
        // Bir buyurtma uchun parallel ishlar ikki marta post ochmasin
        $lock = Cache::lock("team_bot:order:{$orderId}", 30);
        if (! $lock->block(15)) {
            return;
        }
        try {
            $order = Sold::query()->find($orderId);
            if (! $order) {
                return;
            }
            $post = TeamBotPost::for('order', $orderId);
            if (! $post) {
                if (! $create || ! $this->shouldPost($order)) {
                    return;
                }
            }
            [$text, $rows] = $this->renderOrder($order, $post);
            $created = $this->upsertPost('order', $orderId, $post, $text, $rows, $this->threadId('orders'));
            if ($created) {
                $created->putMeta('flow', self::paymentCode($order) === PaymentStatusCode::CASH_PENDING ? 'cash' : 'card');
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Xavfsizlik to'ri: buyurtma jadvali to'g'ridan-to'g'ri (observer'siz)
     * o'zgargan bo'lsa ham guruhdagi xabar eskirib qolmasin — oxirgi 7 kunlik
     * postlardan buyurtmasi keyin o'zgarganlari qayta chiziladi.
     */
    public function resyncRecent(bool $all = false): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $ids = DB::table('team_bot_posts as p')
            ->join('solds as s', 's.id', '=', 'p.ref_id')
            ->where('p.kind', 'order')
            ->where('p.created_at', '>', now()->subDays(7))
            ->when(! $all, fn ($q) => $q->whereColumn('s.updated_at', '>', 'p.updated_at'))
            ->limit($all ? 1000 : 100)
            ->pluck('p.ref_id');
        foreach ($ids as $id) {
            $this->syncOrder((int) $id, false);
            TeamBotPost::query()->where('kind', 'order')->where('ref_id', $id)->update(['updated_at' => now()]);
        }

        return $ids->count();
    }

    // ─── VAZIFA XABARI ───────────────────────────────────────────────────────

    public function renderTask(TeamTask $task): array
    {
        $task->loadMissing('assignee', 'creator');
        $prio = ['high' => '🔴 Shoshilinch', 'normal' => '🟡 O‘rta', 'low' => '⚪ Past'][$task->priority] ?? '';
        $statusIcon = ['todo' => '⬜', 'doing' => '🟦', 'done' => '✅', 'cancelled' => '🚫'][$task->status] ?? '';

        $lines = [];
        $lines[] = "📝 <b>Vazifa #{$task->id}</b> · {$prio}";
        $lines[] = '';
        $title = e($task->title);
        $lines[] = $task->status === 'done' || $task->status === 'cancelled' ? "<s>{$title}</s>" : "<b>{$title}</b>";
        if ($task->description) {
            $lines[] = e(Str::limit($task->description, 500));
        }
        $lines[] = '';
        $lines[] = "👤 Mas'ul: ".self::mention($task->assignee);
        if ($task->due_at) {
            $due = $task->due_at->timezone('Asia/Tashkent')->format('d.m H:i');
            $lines[] = '⏰ Muddat: '.($task->isOverdue() ? "<b>{$due} — muddati o'tdi</b>" : $due);
        }
        if ($task->order_id) {
            $lines[] = "🔗 Buyurtma #{$task->order_id}";
        }
        $lines[] = "{$statusIcon} Holat: <b>".e(TeamTask::STATUSES[$task->status] ?? $task->status).'</b>';
        $lines[] = '✍️ '.e($task->creator?->name ?? 'Tizim');

        $rows = [];
        $row = [];
        if ($task->isOpen() && ! $task->assignee_admin_id) {
            $row[] = ['text' => '🙋 Men olaman', 'callback_data' => "t:{$task->id}:take"];
        }
        if ($task->status === 'todo') {
            $row[] = ['text' => '▶️ Boshladim', 'callback_data' => "t:{$task->id}:doing"];
        }
        if ($task->isOpen()) {
            $row[] = ['text' => '✅ Bajarildi', 'callback_data' => "t:{$task->id}:done"];
        }
        if (in_array($task->status, ['done', 'cancelled'], true)) {
            $row[] = ['text' => '🔄 Qayta ochish', 'callback_data' => "t:{$task->id}:todo"];
        }
        if ($row) {
            $rows[] = $row;
        }
        $url = $this->panelUrl('boshqaruv.tasks');
        if ($url) {
            $rows[] = [['text' => '🗂 Barcha vazifalar', 'url' => $url]];
        }

        return [implode("\n", $lines), $rows];
    }

    public function syncTask(int $taskId): void
    {
        if (! $this->enabled()) {
            return;
        }
        $task = TeamTask::query()->find($taskId);
        if (! $task) {
            return;
        }
        [$text, $rows] = $this->renderTask($task);
        $this->upsertPost('task', $taskId, TeamBotPost::for('task', $taskId), $text, $rows, $this->threadId('tasks'));
    }

    /** @return TeamBotPost|null yangi ochilgan post (tahrirlashda null) */
    private function upsertPost(string $kind, int $refId, ?TeamBotPost $post, string $text, array $rows, ?int $thread): ?TeamBotPost
    {
        $hash = hash('sha256', $text.json_encode($rows));
        $markup = ['inline_keyboard' => $rows];
        if ($post) {
            if ($post->content_hash === $hash) {
                return null;
            }
            $ok = $this->tg->call('editMessageText', [
                'chat_id' => $post->chat_id,
                'message_id' => $post->message_id,
                'text' => $text,
                'parse_mode' => 'HTML',
                'link_preview_options' => ['is_disabled' => true],
                'reply_markup' => $markup,
            ]);
            if ($ok) {
                $post->forceFill(['content_hash' => $hash])->save();
            }

            return null;
        }

        $chatId = $this->chatId();
        $sent = $this->tg->call('sendMessage', [
            'chat_id' => $chatId,
            'message_thread_id' => $thread,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            'reply_markup' => $markup,
        ]);
        if (is_array($sent) && isset($sent['message_id'])) {
            return TeamBotPost::query()->updateOrCreate(
                ['kind' => $kind, 'ref_id' => $refId],
                ['chat_id' => $chatId, 'thread_id' => $thread, 'message_id' => (int) $sent['message_id'], 'content_hash' => $hash],
            );
        }

        return null;
    }

    // ─── TUGMALAR (callback_query) ───────────────────────────────────────────

    public function handleCallback(array $cb): void
    {
        $data = (string) ($cb['data'] ?? '');
        $from = (array) ($cb['from'] ?? []);
        $admin = $this->adminFor($from);
        $answer = fn (string $text, bool $alert = false) => $this->tg->call('answerCallbackQuery', [
            'callback_query_id' => $cb['id'], 'text' => $text, 'show_alert' => $alert,
        ]);

        if (! $admin) {
            $answer("Avval akkauntingizni bog'lang: boshqaruv → Vazifalar → «Telegram'ni ulash».", true);

            return;
        }

        $parts = explode(':', $data);
        if (($parts[0] ?? '') === 'o') {
            $this->orderAction((int) ($parts[1] ?? 0), $parts[2] ?? '', $parts[3] ?? null, $admin, $answer);

            return;
        }
        if (($parts[0] ?? '') === 't') {
            $this->taskAction((int) ($parts[1] ?? 0), $parts[2] ?? '', $admin, $answer);

            return;
        }
        $answer('Noma’lum amal');
    }

    private function orderAction(int $orderId, string $action, ?string $arg, Admin $admin, callable $answer): void
    {
        $order = Sold::query()->find($orderId);
        $post = TeamBotPost::for('order', $orderId);
        if (! $order || ! $post) {
            $answer('Buyurtma topilmadi', true);

            return;
        }

        if ($action === 'task') {
            if ($admin->is_read_only ?? false) {
                $answer('Faqat ko‘rish rejimidasiz', true);

                return;
            }
            $task = TeamTask::query()->create([
                'title' => "Buyurtma #{$order->id} bo'yicha",
                'status' => 'todo',
                'priority' => 'normal',
                'assignee_admin_id' => $admin->id,
                'created_by_admin_id' => $admin->id,
                'order_id' => $order->id,
                'source' => 'telegram',
            ]);
            $this->audit($admin, 'Telegram: buyurtmadan vazifa yaratdi', 'team_task', $task->id, ['order_id' => $order->id]);
            $this->syncTask($task->id);
            $answer("Vazifa #{$task->id} yaratildi va sizga biriktirildi");

            return;
        }

        if (! $this->canManageOrders($admin)) {
            $answer('Buyurtmalar bilan ishlash huquqingiz yo‘q', true);

            return;
        }

        $status = self::statusCode($order);
        $open = ! in_array($status, ['delivered', 'customer_received', 'cancelled', 'returned'], true);
        if (! $open && $action !== 'phone') {
            $this->syncOrder($order->id, false);
            $answer('Buyurtma allaqachon yakunlangan yoki bekor qilingan', true);

            return;
        }

        $contact = $this->contactOf($order->loadMissing('user:id,name,lastname,phone_number'));
        $phoneAlert = fn () => Str::limit('📞 '.$contact['name'].': '.($contact['phone'] ?: 'raqam yo‘q')
            .($contact['accountPhone'] ? "\nAkkaunt: ".$contact['accountPhone'] : ''), 195);

        switch ($action) {
            // ── Naqd: qo'ng'iroq oqimi ─────────────────────────────────────
            case 'call':
                $current = $post->metaValue('caller');
                $startedAt = $post->metaValue('call_started_at');
                $fresh = $startedAt && Carbon::parse($startedAt)->gt(now()->subMinutes(20));
                if ($current && (int) $current !== $admin->id && $fresh) {
                    $answer((Admin::find($current)?->name ?? 'Boshqa xodim').' qo‘ng‘iroq qilyapti', true);

                    return;
                }
                $post->putMeta('caller', $admin->id);
                $post->putMeta('call_started_at', now()->toIso8601String());
                if (! $post->metaValue('claimed_by')) {
                    $post->putMeta('claimed_by', $admin->id);
                }
                $this->audit($admin, 'Telegram: mijozga qo‘ng‘iroq qilishni oldi', 'order', $order->id, []);
                $this->sendCallCard($admin, $order, $contact);
                $this->syncOrder($order->id, false);
                $answer($phoneAlert(), true);

                return;

            case 'phone':
                $this->audit($admin, 'Telegram: mijoz raqamini ko‘rdi', 'order', $order->id, []);
                $answer($phoneAlert(), true);

                return;

            case 'ok':
                $post->putMeta('call_status', 'confirmed');
                $post->putMeta('confirmed_by', $admin->id);
                $post->putMeta('confirmed_at', now()->toIso8601String());
                $post->putMeta('reject_pending', null);
                if (! $post->metaValue('claimed_by')) {
                    $post->putMeta('claimed_by', $admin->id);
                }
                $this->audit($admin, 'Telegram: naqd buyurtma mijoz bilan tasdiqlandi', 'order', $order->id, []);
                $this->syncOrder($order->id, false);
                $answer('Tasdiqlandi. Endi qadoqlashga o‘tkazish mumkin');

                return;

            case 'noans':
                $attempts = (int) $post->metaValue('call_attempts', 0) + 1;
                $post->putMeta('call_attempts', $attempts);
                $post->putMeta('last_attempt_at', now()->toIso8601String());
                $this->audit($admin, 'Telegram: mijoz javob bermadi', 'order', $order->id, ['attempt' => $attempts]);
                $this->syncOrder($order->id, false);
                $answer("Belgilandi ({$attempts}-urinish). Birozdan keyin qayta qo‘ng‘iroq qiling");

                return;

            case 'rej':
                if (self::paymentCode($order) !== PaymentStatusCode::CASH_PENDING || ! in_array($status, ['pending', 'packing'], true)) {
                    $answer('Bu buyurtmani botdan bekor qilib bo‘lmaydi — boshqaruvdan bajaring', true);

                    return;
                }
                $post->putMeta('reject_pending', $admin->id);
                $this->syncOrder($order->id, false);
                $answer('Tasdiqlang: buyurtma bekor qilinadi');

                return;

            case 'rejno':
                $post->putMeta('reject_pending', null);
                $this->syncOrder($order->id, false);
                $answer('Bekor qilinmadi');

                return;

            case 'rejyes':
                if (! $post->metaValue('reject_pending')) {
                    $answer('Avval «Mijoz rad etdi» ni bosing', true);

                    return;
                }
                if (self::paymentCode($order) !== PaymentStatusCode::CASH_PENDING || ! in_array($status, ['pending', 'packing'], true)) {
                    $post->putMeta('reject_pending', null);
                    $this->syncOrder($order->id, false);
                    $answer('Holat o‘zgargan — boshqaruvdan tekshiring', true);

                    return;
                }
                $previousStatus = (string) $order->status;
                try {
                    $result = app(OrderService::class)->cancelOrder($order, strict: false);
                } catch (\Throwable $e) {
                    report($e);
                    $answer('Bekor qilib bo‘lmadi: '.Str::limit($e->getMessage(), 150), true);

                    return;
                }
                if (! ($result['ok'] ?? false)) {
                    $answer((string) ($result['message'] ?? 'Bekor qilib bo‘lmadi'), true);

                    return;
                }
                try {
                    app(OrderStatusPushService::class)->sendForTransition($order->fresh(), $previousStatus, 'F');
                } catch (\Throwable $e) {
                    report($e);
                }
                $post->putMeta('reject_pending', null);
                $post->putMeta('call_status', 'refused');
                $post->putMeta('cancelled_by', $admin->id);
                $post->putMeta('cancelled_at', now()->toIso8601String());
                $this->audit($admin, 'Telegram: mijoz rad etdi — buyurtma bekor qilindi', 'order', $order->id, []);
                $this->syncOrder($order->id, false);
                $answer('Buyurtma bekor qilindi');

                return;

            // ── Umumiy: mas'ul va keyingi bosqich ──────────────────────────
            case 'claim':
                $current = $post->metaValue('claimed_by');
                if ($current && (int) $current !== $admin->id) {
                    $answer('Bu buyurtmani '.(Admin::find($current)?->name ?? 'boshqa xodim').' olgan', true);

                    return;
                }
                $post->putMeta('claimed_by', $admin->id);
                $this->audit($admin, 'Telegram: buyurtmani o‘ziga oldi', 'order', $order->id, []);
                $this->syncOrder($order->id, false);
                $answer('Buyurtma sizga biriktirildi');

                return;

            case 'st':
                $allowed = array_column(self::NEXT, 0);
                if ($arg === null || ! in_array($arg, $allowed, true)) {
                    $answer('Bu holatni botdan o‘rnatib bo‘lmaydi', true);

                    return;
                }
                if ($this->flowOf($order, $post) === 'cash' && $status === 'pending' && $post->metaValue('call_status') !== 'confirmed') {
                    $answer('Avval mijozga qo‘ng‘iroq qilib tasdiqlang', true);

                    return;
                }
                try {
                    // forward_only: eski tugma qayta bosilsa holat orqaga ketmaydi
                    app(AdminOrderStatusSyncService::class)->updateMainOrder($order, $arg, ['forward_only' => true]);
                } catch (\Throwable $e) {
                    report($e);
                    $answer('Holatni o‘zgartirib bo‘lmadi: '.Str::limit($e->getMessage(), 150), true);

                    return;
                }
                if (! $post->metaValue('claimed_by')) {
                    $post->putMeta('claimed_by', $admin->id);
                }
                $this->audit($admin, 'Telegram: buyurtma holati', 'order', $order->id, ['status' => $arg]);
                $this->syncOrder($order->id, false);
                $answer('Holat yangilandi');

                return;
        }

        $answer('Noma’lum amal');
    }

    /** Qo'ng'iroq qiluvchiga shaxsiy xabar: to'liq raqam (bosib qo'ng'iroq qilinadi), manzil va tarkib */
    private function sendCallCard(Admin $admin, Sold $order, array $contact): void
    {
        if (! $admin->telegram_user_id) {
            return;
        }
        $address = $contact['address'];
        $fullAddress = $address['fullAddress'] ?? $address['full_address'] ?? $address['address']
            ?? collect([$address['region'] ?? null, $address['district'] ?? null, $address['street'] ?? null, $address['home'] ?? null])->filter()->implode(', ');
        $items = $this->itemsOf($order);
        $lines = [
            "📞 <b>#{$order->id} — mijozga qo'ng'iroq</b>",
            '',
            '👤 '.e($contact['name']),
            '☎️ '.e($contact['phone'] ?: 'raqam yo‘q'),
        ];
        if ($contact['accountPhone']) {
            $lines[] = '☎️ Akkaunt: '.e($contact['accountPhone']);
        }
        if ($fullAddress) {
            $lines[] = '📍 '.e(Str::limit((string) $fullAddress, 200));
        }
        $lines[] = '💰 '.number_format((float) ($order->amount ?? 0), 0, '.', ' ')." so'm · naqd";
        foreach ($items->take(8) as $it) {
            $lines[] = '   • '.e(Str::limit($it['name'], 60)).($it['qty'] > 1 ? " × {$it['qty']}" : '');
        }
        if (trim((string) $order->buyerWish) !== '') {
            $lines[] = '💬 '.e(Str::limit(trim((string) $order->buyerWish), 300));
        }
        $lines[] = '';
        $lines[] = "Gaplashib bo'lgach guruhdagi xabarda «✅ Tasdiqladi», «📵 Javob bermadi» yoki «🚫 Mijoz rad etdi» ni bosing.";

        $this->tg->call('sendMessage', [
            'chat_id' => $admin->telegram_user_id,
            'text' => implode("\n", $lines),
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ]);
    }

    private function taskAction(int $taskId, string $action, Admin $admin, callable $answer): void
    {
        $task = TeamTask::query()->find($taskId);
        if (! $task) {
            $answer('Vazifa topilmadi', true);

            return;
        }
        if ($admin->is_read_only ?? false) {
            $answer('Faqat ko‘rish rejimidasiz', true);

            return;
        }
        switch ($action) {
            case 'take':
                if ($task->assignee_admin_id && $task->assignee_admin_id !== $admin->id) {
                    $answer('Vazifa allaqachon '.($task->assignee?->name ?? 'boshqa xodim').'da', true);

                    return;
                }
                $task->assignee_admin_id = $admin->id;
                $task->save();
                $msg = 'Vazifa sizga biriktirildi';
                break;
            case 'doing':
            case 'done':
            case 'todo':
                if (! $task->assignee_admin_id) {
                    $task->assignee_admin_id = $admin->id;
                }
                $task->setStatus($action);
                $msg = ['doing' => 'Omad! Jarayonda', 'done' => 'Zo‘r, bajarildi 🎉', 'todo' => 'Qayta ochildi'][$action];
                break;
            default:
                $answer('Noma’lum amal');

                return;
        }
        $this->audit($admin, 'Telegram: vazifa '.$action, 'team_task', $task->id, []);
        $this->syncTask($task->id);
        $answer($msg);
    }

    // ─── XABARLAR (buyruqlar) ────────────────────────────────────────────────

    public function handleMessage(array $msg): void
    {
        $text = trim((string) ($msg['text'] ?? ''));
        if ($text === '' || ! str_starts_with($text, '/')) {
            return;
        }
        $chat = (array) ($msg['chat'] ?? []);
        $from = (array) ($msg['from'] ?? []);
        $thread = $msg['message_thread_id'] ?? null;
        [$cmd, $rest] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $cmd = strtolower(explode('@', ltrim($cmd, '/'))[0]);
        $reply = fn (string $t) => $this->tg->call('sendMessage', [
            'chat_id' => $chat['id'],
            'message_thread_id' => $thread,
            'text' => $t,
            'parse_mode' => 'HTML',
            'reply_parameters' => ['message_id' => $msg['message_id'], 'allow_sending_without_reply' => true],
            'link_preview_options' => ['is_disabled' => true],
        ]);

        if ($cmd === 'start') {
            if (str_starts_with($rest, 'link_') && ($chat['type'] ?? '') === 'private') {
                $admin = $this->linkByToken(substr($rest, 5), $from);
                $reply($admin
                    ? '✅ Ulandi: <b>'.e($admin->name).'</b>. Endi guruhdagi tugmalar siz nomingizdan ishlaydi.'
                    : "⚠️ Havola eskirgan yoki noto'g'ri. Boshqaruvda «Telegram'ni ulash»ni qayta bosing.");

                return;
            }
            $reply("Salom! Bu Kitobchi jamoa boti.\n\n/vazifa matn — yangi vazifa (buyurtma xabariga javob qilib yozsangiz, o'sha buyurtmaga bog'lanadi; @username — mas'ul)\n/vazifalarim — menga biriktirilgan ochiq vazifalar\n/vazifalar — jamoaning ochiq vazifalari\n/myid — Telegram ID");

            return;
        }
        if ($cmd === 'myid') {
            $reply('🪪 <code>'.(int) ($from['id'] ?? 0).'</code>');

            return;
        }

        $admin = $this->adminFor($from);
        if (! $admin) {
            $reply("Avval akkauntingizni bog'lang: boshqaruv → Vazifalar → «Telegram'ni ulash».");

            return;
        }

        if ($cmd === 'setup') {
            $this->setup($chat, $admin, $reply);

            return;
        }

        if (in_array($cmd, ['vazifa', 'task'], true)) {
            $this->createTaskFromMessage($msg, $rest, $admin, $reply);

            return;
        }

        if (in_array($cmd, ['vazifalarim', 'mytasks'], true)) {
            $reply($this->taskList(TeamTask::query()->where('assignee_admin_id', $admin->id), 'Sizning ochiq vazifalaringiz'));

            return;
        }
        if (in_array($cmd, ['vazifalar', 'tasks'], true)) {
            $reply($this->taskList(TeamTask::query(), 'Jamoaning ochiq vazifalari'));

            return;
        }
    }

    private function setup(array $chat, Admin $admin, callable $reply): void
    {
        if (! $admin->isSuperAdmin()) {
            $reply('Faqat superadmin sozlay oladi.');

            return;
        }
        if (! in_array($chat['type'] ?? '', ['group', 'supergroup'], true)) {
            $reply('Buyruqni jamoa guruhida yozing.');

            return;
        }
        $this->putSetting('chat_id', (string) $chat['id']);
        $made = [];
        if (! empty($chat['is_forum'])) {
            foreach (['orders' => ['🛒 Buyurtmalar', 7322096], 'tasks' => ['📝 Vazifalar', 16478047], 'alerts' => ['🔔 Ogohlantirishlar', 16766590]] as $key => [$name, $color]) {
                if ($this->threadId($key)) {
                    continue;
                }
                $topic = $this->tg->call('createForumTopic', ['chat_id' => $chat['id'], 'name' => $name, 'icon_color' => $color]);
                if (is_array($topic) && isset($topic['message_thread_id'])) {
                    $this->putSetting("thread_{$key}", (string) $topic['message_thread_id']);
                    $made[] = $name;
                }
            }
        }
        $reply("✅ Guruh ulandi.\n".($made ? 'Yaratilgan mavzular: '.e(implode(', ', $made)) : (empty($chat['is_forum']) ? "Maslahat: guruhda «Mavzular» (Topics)ni yoqsangiz, buyurtma, vazifa va ogohlantirishlar alohida bo'limlarga tushadi — keyin /setup ni qayta yuboring." : 'Mavzular allaqachon bor.')));
    }

    private function createTaskFromMessage(array $msg, string $rest, Admin $admin, callable $reply): void
    {
        $title = trim($rest);
        $replyTo = (array) ($msg['reply_to_message'] ?? []);
        if ($title === '' && ! empty($replyTo['text'])) {
            $title = Str::limit(trim(strtok((string) $replyTo['text'], "\n")), 120, '');
        }
        if ($title === '') {
            $reply("Vazifa matnini yozing: <code>/vazifa Kuryerga qo'ng'iroq qilish @username</code>");

            return;
        }

        // @username — mas'ul; "!!" — shoshilinch
        $assignee = $admin;
        if (preg_match('/@([A-Za-z0-9_]{4,32})/', $title, $m)) {
            $found = Admin::query()->where('telegram_username', $m[1])->where('is_active', true)->first();
            if ($found) {
                $assignee = $found;
                $title = trim(str_replace($m[0], '', $title));
            }
        }
        $priority = 'normal';
        if (str_contains($title, '!!')) {
            $priority = 'high';
            $title = trim(str_replace('!!', '', $title));
        }

        $orderId = null;
        if (! empty($replyTo['message_id'])) {
            $orderId = TeamBotPost::query()->where('kind', 'order')
                ->where('chat_id', (int) ($msg['chat']['id'] ?? 0))
                ->where('message_id', (int) $replyTo['message_id'])
                ->value('ref_id');
        }

        $task = TeamTask::query()->create([
            'title' => Str::limit($title, 250, ''),
            'status' => 'todo',
            'priority' => $priority,
            'assignee_admin_id' => $assignee->id,
            'created_by_admin_id' => $admin->id,
            'order_id' => $orderId,
            'source' => 'telegram',
        ]);
        $this->audit($admin, 'Telegram: vazifa yaratdi', 'team_task', $task->id, []);
        $this->syncTask($task->id);
        // Vazifalar mavzusi alohida bo'lsa — yozilgan joyga qisqa tasdiq
        if ($this->threadId('tasks') && ($msg['message_thread_id'] ?? null) != $this->threadId('tasks')) {
            $reply("📝 Vazifa #{$task->id} yaratildi — «Vazifalar» bo'limida.");
        }
    }

    private function taskList($query, string $title): string
    {
        $tasks = $query->whereIn('status', ['todo', 'doing'])
            ->with('assignee')
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderBy('due_at')
            ->limit(15)->get();
        if ($tasks->isEmpty()) {
            return "🎉 {$title}: hech narsa yo'q.";
        }
        $lines = ["<b>{$title}</b>"];
        foreach ($tasks as $t) {
            $flag = $t->priority === 'high' ? '🔴' : ($t->status === 'doing' ? '🟦' : '⬜');
            $due = $t->due_at ? ' · ⏰ '.$t->due_at->timezone('Asia/Tashkent')->format('d.m H:i').($t->isOverdue() ? ' ❗' : '') : '';
            $lines[] = "{$flag} #{$t->id} ".e(Str::limit($t->title, 60)).' — '.e($t->assignee?->name ?? '—').$due;
        }

        return implode("\n", $lines);
    }

    // ─── KUNLIK XULOSA VA ESLATMALAR ─────────────────────────────────────────

    public function sendDigest(): void
    {
        if (! $this->enabled()) {
            return;
        }
        $tz = 'Asia/Tashkent';
        $appTz = (string) config('app.timezone', 'UTC');
        $from = Carbon::now($tz)->subDay()->startOfDay()->setTimezone($appTz);
        $to = Carbon::now($tz)->subDay()->endOfDay()->setTimezone($appTz);
        $orders = Sold::query()->whereBetween('created_at', [$from, $to]);
        $count = (clone $orders)->count();
        $sum = (float) (clone $orders)->sum('amount');
        $cancelled = (clone $orders)->whereIn('status_code', [OrderStatusCode::CANCELLED->value])->count();
        $pendingNow = Sold::query()->where('status_code', OrderStatusCode::PENDING->value)->count();
        $packingNow = Sold::query()->where('status_code', OrderStatusCode::PACKING->value)->count();
        $openTasks = TeamTask::query()->whereIn('status', ['todo', 'doing'])->count();
        $overdue = TeamTask::query()->whereIn('status', ['todo', 'doing'])->whereNotNull('due_at')->where('due_at', '<', now())->with('assignee')->limit(5)->get();
        $doneYesterday = TeamTask::query()->where('status', 'done')->whereBetween('completed_at', [$from, $to])->count();

        $lines = [
            '☀️ <b>Xayrli tong, jamoa!</b> Kecha ('.Carbon::now($tz)->subDay()->format('d.m').'):',
            '',
            "🛒 Buyurtmalar: <b>{$count}</b> · ".number_format($sum, 0, '.', ' ')." so'm".($cancelled ? " · bekor: {$cancelled}" : ''),
            "📝 Bajarilgan vazifalar: <b>{$doneYesterday}</b>",
            '',
            '<b>Hozir:</b>',
            "⏳ Kutilmoqda: {$pendingNow} · 📦 Qadoqlanmoqda: {$packingNow}",
            "🗂 Ochiq vazifalar: {$openTasks}",
        ];
        if ($overdue->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "❗ <b>Muddati o'tganlar:</b>";
            foreach ($overdue as $t) {
                $lines[] = "• #{$t->id} ".e(Str::limit($t->title, 60)).' — '.self::mention($t->assignee);
            }
        }
        $this->tg->call('sendMessage', [
            'chat_id' => $this->chatId(),
            'message_thread_id' => $this->threadId('alerts'),
            'text' => implode("\n", $lines),
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ]);
    }

    /**
     * Bir martalik eslatmalar:
     *  • naqd buyurtma 30 daqiqadan beri tasdiqlanmagan (hech kim qo'ng'iroq qilmagan yoki natija yo'q);
     *  • buyurtma $orderMinutes dan beri «Kutilmoqda» holatida;
     *  • muddati o'tgan vazifalar.
     */
    public function remindStale(int $orderMinutes = 120, int $callMinutes = 30): void
    {
        if (! $this->enabled()) {
            return;
        }
        $posts = TeamBotPost::query()->where('kind', 'order')->where('created_at', '>', now()->subDays(3))->get()->keyBy('ref_id');
        if ($posts->isNotEmpty()) {
            $pending = Sold::query()
                ->whereIn('id', $posts->keys())
                ->where('status_code', OrderStatusCode::PENDING->value)
                ->get();
            foreach ($pending as $order) {
                $post = $posts[$order->id];
                $isCash = $this->flowOf($order, $post) === 'cash';
                $unconfirmed = $isCash && $post->metaValue('call_status') !== 'confirmed';

                if ($unconfirmed && ! $post->metaValue('call_sla_sent') && $post->created_at->lt(now()->subMinutes($callMinutes))) {
                    $caller = $post->metaValue('caller') ? Admin::find($post->metaValue('caller')) : null;
                    $attempts = (int) $post->metaValue('call_attempts', 0);
                    $text = $caller
                        ? "📞 #{$order->id} naqd buyurtma hali tasdiqlanmagan — ".self::mention($caller).($attempts ? ", {$attempts} marta javob bermadi" : ', natijani belgilang')
                        : "📞 #{$order->id} naqd buyurtma {$callMinutes} daqiqadan beri kutyapti — kim qo‘ng‘iroq qiladi?";
                    $this->replyToPost($post, $text);
                    $post->putMeta('call_sla_sent', now()->toIso8601String());

                    continue;
                }

                if (! $unconfirmed && ! $post->metaValue('sla_sent') && $order->created_at->lt(now()->subMinutes($orderMinutes))) {
                    $who = $post->metaValue('claimed_by') ? self::mention(Admin::find($post->metaValue('claimed_by'))) : 'kim oladi?';
                    $hours = max(1, (int) floor($order->created_at->diffInMinutes(now()) / 60));
                    $this->replyToPost($post, "⏰ #{$order->id} {$hours} soatdan beri «Kutilmoqda» holatida — {$who}");
                    $post->putMeta('sla_sent', now()->toIso8601String());
                }
            }
        }

        $tasks = TeamTask::query()->whereIn('status', ['todo', 'doing'])
            ->whereNotNull('due_at')->where('due_at', '<', now())->whereNull('reminded_at')
            ->with('assignee')->limit(20)->get();
        foreach ($tasks as $task) {
            $post = TeamBotPost::for('task', $task->id);
            $this->tg->call('sendMessage', [
                'chat_id' => $post?->chat_id ?? $this->chatId(),
                'message_thread_id' => $post?->thread_id ?? $this->threadId('tasks'),
                'text' => "❗ Vazifa #{$task->id} muddati o'tdi — ".self::mention($task->assignee),
                'parse_mode' => 'HTML',
                'reply_parameters' => $post ? ['message_id' => $post->message_id, 'allow_sending_without_reply' => true] : null,
            ]);
            $task->forceFill(['reminded_at' => now()])->save();
            $this->syncTask($task->id);
        }
    }

    private function replyToPost(TeamBotPost $post, string $text): void
    {
        $this->tg->call('sendMessage', [
            'chat_id' => $post->chat_id,
            'message_thread_id' => $post->thread_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_parameters' => ['message_id' => $post->message_id, 'allow_sending_without_reply' => true],
        ]);
    }

    // ─── YORDAMCHILAR ────────────────────────────────────────────────────────

    /** Telegram URL tugmasi faqat https bo'lsa qabul qiladi (localhost'da tugma qo'shilmaydi) */
    private function panelUrl(string $route, array $params = []): ?string
    {
        try {
            $url = route($route, $params);
        } catch (\Throwable) {
            return null;
        }

        return str_starts_with($url, 'https://') ? $url : null;
    }

    private function audit(Admin $admin, string $action, string $type, int $id, array $data): void
    {
        try {
            AdminAuditLog::query()->create([
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'method' => 'BOT',
                'route_name' => 'team-bot',
                'path' => '/telegram/team-webhook',
                'action' => $action,
                'target_type' => $type,
                'target_id' => $id,
                'request_data' => $data,
                'ip_address' => 'telegram',
                'user_agent' => 'Kitobchi team bot',
                'status_code' => 200,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
