<?php

namespace App\Http\Controllers\Boshqaruv;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BotTicketAttachment;
use App\Models\Sold;
use App\Models\SupportTemplate;
use App\Services\Support\SupportInboxService;
use App\Services\TelegramSupportService;
use App\Support\AdminOrderStatusPresenter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Boshqaruv → Support inbox (Telegram Web uslubidagi operator ish joyi) va Support KPI.
 */
class SupportInboxController extends Controller
{
    public function __construct(private SupportInboxService $inbox)
    {
    }

    private function me(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('panel')->user();
        abort_unless($admin, 401);

        return $admin;
    }

    // ─── SAHIFALAR ───────────────────────────────────────────────────────────

    public function page(Request $request): Response
    {
        $me = $this->me();
        $segment = $request->query('segment') === 'shop' ? 'shop' : ($request->query('segment') === 'chat_monitor' && $me->isSuperAdmin() ? 'chat_monitor' : 'customer');
        $filter = (string) $request->query('filter', 'open');

        return Inertia::render('SupportInbox', [
            'inbox' => [
                'me' => [
                    'id' => (int) $me->id,
                    'name' => $me->name,
                    'readOnly' => (bool) ($me->is_read_only ?? false),
                    'isSuperAdmin' => (bool) $me->isSuperAdmin(),
                ],
                'agents' => $this->agents(),
                'templates' => $this->templatesPayload(),
                'realtime' => $this->realtimeConfig($request),
                'initial' => [
                    'segment' => $segment,
                    'filter' => $filter,
                    'key' => (string) $request->query('key', ''),
                    'list' => $segment === 'chat_monitor' ? ['items' => [], 'next' => null, 'counts' => [], 'segments' => ['customer' => ['open' => 0, 'unread' => 0], 'shop' => ['open' => 0, 'unread' => 0]]] : $this->inbox->threads($segment, $filter, '', $me),
                ],
                'urls' => [
                    'threads' => route('boshqaruv.support.inbox.threads'),
                    'thread' => route('boshqaruv.support.inbox.thread'),
                    'older' => route('boshqaruv.support.inbox.older'),
                    'reply' => route('boshqaruv.support.inbox.reply'),
                    'assign' => route('boshqaruv.support.inbox.assign'),
                    'close' => route('boshqaruv.support.inbox.close'),
                    'read' => route('boshqaruv.support.inbox.read'),
                    'templates' => route('boshqaruv.support.inbox.templates.store'),
                    'kpi' => route('boshqaruv.support.kpi'),
                    'chatMonitor' => route('boshqaruv.support.inbox.chat.monitor'),
                    'chatData' => url('/boshqaruv/chat'),
                    'customerOrders' => route('boshqaruv.support.inbox.customer-orders'),
                    'broadcastAuth' => route('boshqaruv.broadcasting.auth'),
                ],
            ],
        ]);
    }

    public function kpiPage(Request $request): Response
    {
        [$from, $to, $range] = $this->range($request);

        return Inertia::render('SupportKpi', [
            'kpi' => $this->inbox->kpi($from, $to),
            'kpiFilters' => ['range' => $range, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'inboxUrl' => route('boshqaruv.support.inbox'),
        ]);
    }

    // ─── JSON API ────────────────────────────────────────────────────────────

    public function threads(Request $request): JsonResponse
    {
        $segment = $request->query('segment') === 'shop' ? 'shop' : 'customer';

        return response()->json($this->inbox->threads(
            $segment,
            (string) $request->query('filter', 'all'),
            (string) $request->query('q', ''),
            $this->me(),
            $request->query('before') ?: null,
        ));
    }

    public function thread(Request $request): JsonResponse
    {
        $key = (string) $request->query('key', '');

        return $this->guard(fn () => $this->inbox->thread($key, $this->me()));
    }

    public function older(Request $request): JsonResponse
    {
        $key = (string) $request->query('key', '');
        $before = (int) $request->query('before_id', 0);

        return $this->guard(fn () => $this->inbox->olderMessages($key, $before));
    }

    public function reply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:4000'],
            'note' => ['sometimes', 'boolean'],
            'template_id' => ['nullable', 'integer'],
        ]);

        return $this->guard(function () use ($data) {
            $message = $this->inbox->reply($data['key'], $this->me(), $data['message'], (bool) ($data['note'] ?? false));

            if (! empty($data['template_id'])) {
                SupportTemplate::query()->whereKey($data['template_id'])->increment('usage_count');
            }

            return ['message' => $message];
        });
    }

    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'admin_id' => ['nullable', 'integer'],
        ]);

        return $this->guard(fn () => ['thread' => $this->inbox->assign($data['key'], $this->me(), $data['admin_id'] ?? null)]);
    }

    public function close(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);

        return $this->guard(fn () => ['thread' => $this->inbox->close($data['key'], $this->me())]);
    }

    public function read(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);

        return $this->guard(function () use ($data) {
            $this->inbox->markRead($data['key']);

            return ['ok' => true];
        });
    }

    public function kpi(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return response()->json($this->inbox->kpi($from, $to));
    }

    public function chatMonitorList(Request $request): JsonResponse
    {
        $me = $this->me();
        abort_unless($me->isSuperAdmin(), 403, 'Chat kuzatuv faqat bosh administrator uchun ruxsat etilgan.');

        if (! Schema::hasTable('conversations')) {
            return response()->json([
                'items' => [],
                'next' => null,
                'counts' => ['all' => 0, 'user' => 0, 'seller' => 0],
            ]);
        }

        $tab = (string) $request->query('tab', 'all');
        $search = trim((string) $request->query('q', ''));
        $kitobchiUserId = 1;

        $base = DB::table('conversations')
            ->where('conversations.user_id', '!=', $kitobchiUserId)
            ->where(fn ($nested) => $nested
                ->whereNull('conversations.receiver_id')
                ->orWhere('conversations.receiver_id', '!=', $kitobchiUserId))
            ->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->where('messages.is_deleted', false);
            });

        $query = (clone $base)
            ->select([
                'conversations.id',
                'conversations.shop_id',
                'conversations.receiver_id',
                'conversations.type',
                'conversations.last_message_at',
                'u1.name as user_name',
                'u1.lastname as user_lastname',
                'u1.phone_number as user_phone',
                'u2.name as receiver_name',
                'u2.lastname as receiver_lastname',
                's.shop_name as seller_name',
            ])
            ->selectSub(DB::table('messages')->selectRaw('count(*)')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false), 'messages_count')
            ->selectSub(DB::table('messages')->select('message')->whereColumn('messages.conversation_id', 'conversations.id')->where('is_deleted', false)->latest('created_at')->limit(1), 'last_message')
            ->leftJoin('users as u1', 'u1.id', '=', 'conversations.user_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'conversations.receiver_id')
            ->leftJoin('sellers as s', 's.id', '=', 'conversations.shop_id')
            ->when($tab === 'user', fn ($b) => $b->whereNull('conversations.shop_id'))
            ->when($tab === 'seller', fn ($b) => $b->whereNotNull('conversations.shop_id'))
            ->when($search !== '', fn ($b) => $b->where(fn ($nested) => $nested
                ->where('u1.name', 'like', "%{$search}%")
                ->orWhere('u1.lastname', 'like', "%{$search}%")
                ->orWhere('u1.phone_number', 'like', "%{$search}%")
                ->orWhere('s.shop_name', 'like', "%{$search}%")));

        $conversations = $query->orderByDesc('conversations.last_message_at')->paginate(30)->withQueryString();

        $items = $conversations->getCollection()->map(fn ($c) => [
            'id' => $c->id,
            'kind' => $c->shop_id ? 'seller' : 'user',
            'type' => $c->type,
            'user' => trim(($c->user_name ?? '').' '.($c->user_lastname ?? '')) ?: 'Foydalanuvchi',
            'phone' => $c->user_phone,
            'agent' => $c->seller_name ?: trim(($c->receiver_name ?? '').' '.($c->receiver_lastname ?? '')) ?: 'Foydalanuvchi',
            'messages' => (int) ($c->messages_count ?? 0),
            'lastMsg' => $c->last_message ?: 'Xabar yo\'q',
            'date' => $c->last_message_at ? Carbon::parse($c->last_message_at)->toIso8601String() : null,
            'dataUrl' => route('boshqaruv.chat.data', $c->id),
        ])->values()->all();

        return response()->json([
            'items' => $items,
            'next' => $conversations->hasMorePages() ? (string) ($conversations->currentPage() + 1) : null,
            'counts' => [
                'all' => (int) (clone $base)->count(),
                'user' => (int) (clone $base)->whereNull('shop_id')->count(),
                'seller' => (int) (clone $base)->whereNotNull('shop_id')->count(),
            ],
        ]);
    }

    // ─── SHABLONLAR ──────────────────────────────────────────────────────────

    public function templateStore(Request $request): JsonResponse
    {
        $data = $this->validateTemplate($request);
        SupportTemplate::create($data + ['created_by' => $this->me()->id, 'sort' => (int) SupportTemplate::query()->max('sort') + 1]);

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    public function templateUpdate(Request $request, SupportTemplate $template): JsonResponse
    {
        $template->update($this->validateTemplate($request));

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    public function templateDestroy(SupportTemplate $template): JsonResponse
    {
        $template->update(['is_active' => false]);

        return response()->json(['templates' => $this->templatesPayload()]);
    }

    private function validateTemplate(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'audience' => ['required', 'in:all,customer,shop'],
            'category' => ['nullable', 'string', 'max:40'],
            'shortcut' => ['nullable', 'string', 'max:32', 'regex:/^[\pL\pN_-]+$/u'],
        ]);
        $data['is_active'] = true;

        return $data;
    }

    public static function defaultTemplates(): array
    {
        return [
            ['title' => 'Salomlashish', 'body' => "Assalomu alaykum, {name}! Kitobchi qo'llab-quvvatlash xizmati. Sizga qanday yordam bera olaman?", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'salom'],
            ['title' => 'Operator suhbatga ulandi', 'body' => "Assalomu alaykum! Men inson-operatoringizman. Suhbatni qabul qildim, hozir masalangizni to'liq o'rganib chiqib yordam beraman.", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'ulanish'],
            ['title' => 'Kutib turing', 'body' => "Iltimos, bir oz kutib turing — ma'lumotni tekshirib, hozir javob beraman.", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'kut'],
            ['title' => "Boshqa savol yo'q bo'lsa yopish", 'body' => "Boshqa savollaringiz yoki tushunarsiz joylar qolmadimi? Agar boshqa savolingiz bo'lmasa, suhbatni yakunlayman 😊", 'audience' => 'all', 'category' => 'yakunlash', 'shortcut' => 'yopish'],
            ['title' => 'Javob kutish (Avto-yopish)', 'body' => "Sizdan javob kutmoqdaman. Agar 2 daqiqa ichida javob bo'lmasa, navbatdagi mijozlarga xizmat ko'rsatish maqsadida suhbat avtomatik yakunlanadi.", 'audience' => 'all', 'category' => 'yakunlash', 'shortcut' => 'kutyapman'],
            ['title' => 'Suhbat yakunlandi va xayrlashuv', 'body' => "Murojaatingiz uchun tashakkur! Agar yana savollar paydo bo'lsa, istalgan vaqtda bemalol yozishingiz mumkin. Kuningiz xayrli o'tsin! ✨", 'audience' => 'all', 'category' => 'yakunlash', 'shortcut' => 'xayr'],
            ['title' => 'Buyurtma raqami', 'body' => "Buyurtmangiz holatini aniqlash uchun iltimos buyurtma raqamingizni yuboring.", 'audience' => 'customer', 'category' => 'buyurtma', 'shortcut' => 'buyurtma'],
            ['title' => 'Yetkazish muddati', 'body' => "Toshkent shahri bo'ylab yetkazib berish 24 soat ichida, viloyatlarga 2–3 ish kuni ichida amalga oshiriladi. Kuryer yetkazishdan avval siz bilan bog'lanadi.", 'audience' => 'customer', 'category' => 'yetkazish', 'shortcut' => 'yetkazish'],
            ['title' => 'Kuryer bilan bog\'lanish', 'body' => "Kuryer yetkazish jarayonida va manzilga yetib kelganida siz bilan telefon orqali oldindan bog'lanadi. Iltimos, telefoningiz aloqa doirasida bo'lishiga e'tibor bering.", 'audience' => 'customer', 'category' => 'yetkazish', 'shortcut' => 'kuryer'],
            ['title' => 'Flash Bepul Yetkazish', 'body' => "Bizda har kuni 15 daqiqalik Flash Bepul Yetkazish aksiyasi bo'lib o'tadi! Ilovadagi maxsus banner orqali buyurtma qilsangiz, yetkazib berish mutlaqo bepul bo'ladi.", 'audience' => 'customer', 'category' => 'aksiya', 'shortcut' => 'bepul'],
            ['title' => 'Split (Nasiya / Bo\'lib to\'lash)', 'body' => "Kitobchi Muddatli To'lov (Split) orqali sevimli kitoblaringizni 0% ustamasiz bo'lib to'lashingiz mumkin. Buyurtma berishda to'lov usulidan 'Split'ni tanlang.", 'audience' => 'customer', 'category' => 'moliya', 'shortcut' => 'split'],
            ['title' => 'To\'lov muammosi', 'body' => "To'lov Click, Payme, Uzum bank kartasi yoki kuryerga naqd to'lash orqali amalga oshiriladi. Mablag' yechilib, buyurtma tasdiqlanmagan bo'lsa, to'lov kvitansiyasini (chek) yuboring.", 'audience' => 'customer', 'category' => 'moliya', 'shortcut' => 'tolov'],
            ['title' => 'To\'lov chekini so\'rash', 'body' => "To'lov holatini tekshirib berishimiz uchun, iltimos, to'lov cheki (kvitansiya) rasmini yoki skrinshotini yuboring.", 'audience' => 'customer', 'category' => 'moliya', 'shortcut' => 'chek'],
            ['title' => 'Keshbek', 'body' => "Xaridingizdan hisoblangan keshbek balansingizda saqlanadi va keyingi buyurtmalaringizda to'liq chegirma sifatida ishlatilishi mumkin.", 'audience' => 'customer', 'category' => 'moliya', 'shortcut' => 'keshbek'],
            ['title' => 'Brak kitobni almashtirish', 'body' => "Xavotir olmang! Agar kitobda sahifa yetishmasligi yoki bosma nuqson (brak) aniqlangan bo'lsa, kitob rasmini yuboring — biz uni 7 kun ichida mutlaqo bepul almashtirib beramiz.", 'audience' => 'customer', 'category' => 'kafolat', 'shortcut' => 'almashtirish'],
            ['title' => "Do'kon manzili (faqat onlayn, 24/7)", 'body' => "Kitobchi to'liq onlayn kitob do'koni bo'lib, bizda jismoniy do'kon yoki filial mavjud emas. Buyurtmalarni ilova yoki saytimiz orqali 24 soat (kun-u tun) davomida berishingiz mumkin, barcha buyurtmalar manzilingizgacha yetkazib beriladi 😊", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'manzil'],
            ['title' => '24/7 Buyurtma qabul qilish', 'body' => "Kitobchi ilovasi va sayti orqali buyurtmalar 24 soat davomida to'xtovsiz qabul qilinadi. Barcha kitoblar to'g'ridan-to'g'ri manzilingizgacha yetkazib beriladi 🚚", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'online'],
            ['title' => 'Kitob topilmadi', 'body' => "Agar siz qidirgan kitob ilovada topilmasa, kitob nomi va muallifini qoldiring, hamkor nashriyotlar va do'konlardan topib, katalogga qo'shib beramiz.", 'audience' => 'customer', 'category' => 'katalog', 'shortcut' => 'kitob'],
            ['title' => 'Buyurtma holati', 'body' => "Buyurtmangiz tayyorlandi va kuryerga topshirildi. Kuryer yetkazish vaqtida siz bilan telefon orqali bog'lanadi.", 'audience' => 'customer', 'category' => 'buyurtma', 'shortcut' => 'holat'],
            ['title' => 'Buyurtmani bekor qilish', 'body' => "Buyurtmangiz bekor qilindi. Agar to'lov oldindan amalga oshirilgan bo'lsa, mablag' 1–3 ish kuni ichida bank kartangizga qaytariladi.", 'audience' => 'customer', 'category' => 'buyurtma', 'shortcut' => 'bekor'],
            ['title' => 'Do\'kon: moderatsiya', 'body' => "Mahsulotingiz moderatsiyada. Odatda 24 soat ichida ko'rib chiqiladi. Rad etilsa, sababi seller kabinetida ko'rsatiladi.", 'audience' => 'shop', 'category' => 'katalog', 'shortcut' => 'moderatsiya'],
            ['title' => 'Do\'kon: pul yechish', 'body' => "Do'kon hisobidan pul yechish so'rovi 1–3 ish kunida bank hisob raqamingizga o'tkazib beriladi.", 'audience' => 'shop', 'category' => 'moliya', 'shortcut' => 'pul'],
            ['title' => 'Do\'kon: buyurtma tayyorlash', 'body' => "Buyurtmani 'Tayyor' deb belgilang — tizim avtomatik kuryer biriktiradi va mahsulotni olib ketadi.", 'audience' => 'shop', 'category' => 'buyurtma', 'shortcut' => 'tayyor'],
            ['title' => 'Rahmat / Hal qilindi', 'body' => "Murojaatingiz uchun tashakkur! Har doim xizmatingizdamiz. Kitobchi bilan qolganingiz uchun rahmat, maroqli mutolaa tilaymiz!", 'audience' => 'all', 'category' => 'umumiy', 'shortcut' => 'rahmat'],
        ];
    }

    private function templatesPayload(): array
    {
        if (Schema::hasTable('support_templates')) {
            $now = now();
            foreach (self::defaultTemplates() as $i => $row) {
                SupportTemplate::query()->updateOrCreate(
                    ['shortcut' => $row['shortcut']],
                    array_merge($row, [
                        'sort' => $i,
                        'is_active' => true,
                    ])
                );
            }
        }

        return SupportTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (SupportTemplate $t) => [
                'id' => (int) $t->id,
                'title' => $t->title,
                'body' => $t->body,
                'audience' => $t->audience,
                'category' => $t->category,
                'shortcut' => $t->shortcut,
                'usage' => (int) $t->usage_count,
            ])->all();
    }

    // ─── MEDIA (Telegram fayllari) ──────────────────────────────────────────

    public function media(BotTicketAttachment $attachment, TelegramSupportService $telegram): StreamedResponse
    {
        $file = Cache::remember('support-media:' . $attachment->id, now()->addMinutes(50), fn () => $telegram->resolveFile((string) $attachment->file_id));
        abort_unless($file, 404, 'Fayl topilmadi yoki 20 MB dan katta.');

        $mime = match (strtolower(pathinfo($file['path'], PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'oga', 'ogg' => 'audio/ogg',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
        $name = $attachment->file_name ?: basename($file['path']);

        return response()->stream(function () use ($file) {
            $response = Http::withOptions(['stream' => true])->timeout(60)->get($file['url']);
            $body = $response->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(65536);
                flush();
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => (str_starts_with($mime, 'image/') || str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/') ? 'inline' : 'attachment') . '; filename="' . addslashes($name) . '"',
            'Cache-Control' => 'private, max-age=3000',
        ]);
    }

    // ─── REAL-VAQT AVTORIZATSIYASI ───────────────────────────────────────────

    // ─── MIJOZ BUYURTMALARI (o'ng panel) ─────────────────────────────────────
    // Operator suhbatdan chiqmasdan mijozning buyurtmalarini ko'radi. Amallar
    // (status, bekor qilish, refund...) faqat "Buyurtmalar" huquqi bor va
    // faqat-ko'rish rejimida bo'lmagan xodimga ochiq; qolganlarga — ko'rish.

    public function customerOrders(Request $request): JsonResponse
    {
        $me = $this->me();
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = Sold::query()
            ->where('user_id', (int) $data['user_id'])
            ->latest('id')
            ->paginate(15, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'items' => collect($paginator->items())->map(function (Sold $order) {
                $payment = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);

                return [
                    'id' => (int) $order->id,
                    'amount' => (float) ($order->amount ?? 0),
                    'status' => OrderStatusCode::fromLegacy($order->status_code ?? $order->status)->value,
                    'statusLabel' => AdminOrderStatusPresenter::mainOrder($order->status_code ?? $order->status),
                    'paymentCode' => $payment->value,
                    'payment' => AdminOrderStatusPresenter::payment($payment->value),
                    'items' => (int) collect($order->items ?? [])->sum(fn ($item) => max(1, (int) ($item['count_item'] ?? $item['count'] ?? $item['quantity'] ?? 1))),
                    'date' => optional($order->created_at)->format('d.m.Y H:i'),
                    'deliveryDate' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d.m.Y') : null,
                    'dataUrl' => route('boshqaruv.support.inbox.customer-order', $order),
                ];
            })->values()->all(),
            'page' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'canManage' => $this->canManageOrders($me),
        ]);
    }

    public function customerOrder(Sold $order): JsonResponse
    {
        $me = $this->me();
        $payload = app(AdminController::class)->orderData($order)->getData(true);
        $canManage = $this->canManageOrders($me);
        if (! $canManage) {
            $payload = $this->stripOrderActions($payload);
        }
        $payload['canManage'] = $canManage;

        return response()->json($payload);
    }

    private function canManageOrders(Admin $me): bool
    {
        if ($me->is_read_only ?? false) {
            return false;
        }

        return $me->isSuperAdmin() || $me->hasPermission('orders');
    }

    /**
     * Faqat-ko'rish: amal URL'lari va "can*" bayroqlari olib tashlanadi —
     * tugmalar ko'rinmaydi, server esa baribir panel.permission:orders bilan
     * himoyalangan (ikki qavatli himoya).
     */
    private function stripOrderActions(array $data, ?string $parent = null): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->stripOrderActions($value, is_string($key) ? $key : $parent);
                continue;
            }
            if (! is_string($key)) {
                continue;
            }
            // Fiskal chek kvitansiyasi (soliq saytidagi) va mahsulot havolasi — amal emas
            $keep = $key === 'productUrl'
                || ($parent === 'fiscalReceipt' && in_array($key, ['receiptUrl', 'refundReceiptUrl'], true));
            if (! $keep && str_ends_with($key, 'Url')) {
                $data[$key] = null;
            }
            if (in_array($key, ['canRefundPayment', 'canPayPendingCard', 'canSendUnreachablePush', 'canRefund', 'canReassign'], true)) {
                $data[$key] = false;
            }
        }

        return $data;
    }

    public function broadcastingAuth(Request $request)
    {
        return Broadcast::auth($request);
    }

    // ─── YORDAMCHILAR ────────────────────────────────────────────────────────

    private function guard(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function agents(): array
    {
        return $this->inbox->supportAdmins()
            ->map(fn (Admin $a) => ['id' => (int) $a->id, 'name' => $a->name])
            ->values()
            ->all();
    }

    private function realtimeConfig(Request $request): ?array
    {
        $driver = (string) config('broadcasting.default');
        if (! in_array($driver, ['reverb', 'pusher'], true)) {
            return null;
        }

        $connection = (array) config('broadcasting.connections.' . $driver, []);
        $key = (string) ($connection['key'] ?? '');
        if ($key === '') {
            return null;
        }

        $client = (array) config('broadcasting.panel_client', []);
        $scheme = (string) ($client['scheme'] ?: 'https');

        return [
            'driver' => $driver,
            'key' => $key,
            'cluster' => $connection['options']['cluster'] ?? 'mt1',
            'host' => (string) ($client['host'] ?: $request->getHost()),
            'port' => (int) ($client['port'] ?: 443),
            'tls' => $scheme === 'https',
        ];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $range = (string) $request->query('range', '7d');
        $to = now()->endOfDay();

        switch ($range) {
            case 'today':
                $from = now()->startOfDay();
                break;
            case '30d':
                $from = now()->subDays(29)->startOfDay();
                break;
            case 'custom':
                try {
                    $from = Carbon::parse((string) $request->query('from'))->startOfDay();
                    $to = Carbon::parse((string) $request->query('to'))->endOfDay();
                } catch (\Throwable) {
                    $from = now()->subDays(6)->startOfDay();
                }
                if ($from->gt($to)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }
                if ($from->diffInDays($to) > 92) {
                    $from = $to->copy()->subDays(91)->startOfDay();
                }
                break;
            default:
                $range = '7d';
                $from = now()->subDays(6)->startOfDay();
        }

        return [$from, $to, $range];
    }
}
