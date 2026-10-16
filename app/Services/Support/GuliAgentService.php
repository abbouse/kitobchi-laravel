<?php

namespace App\Services\Support;

use App\Models\Books;
use App\Models\BotTicket;
use App\Models\BotTicketMessage;
use App\Models\Conversation;
use App\Models\ProjectSetting;
use App\Models\Sold;
use App\Models\User;
use App\Services\ChatBotKnowledgeService;
use App\Services\OpenAIService;
use App\Services\SessionService;
use App\Services\SupportChatBridgeService;
use App\Services\TelegramSupportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GuliAgentService
{
    public function __construct(
        protected OpenAIService $openai,
        protected ChatBotKnowledgeService $knowledge,
    ) {
    }

    /**
     * Ticket Guli tomonidan ko'rib chiqilishi kerakmi?
     */
    public function shouldHandle(BotTicket $ticket): bool
    {
        // 1. Agar ticket yopilgan bo'lsa — AI aralashmaydi
        if (in_array($ticket->status, ['closed', 'rated'], true)) {
            return false;
        }

        // 2. Agar tirik inson-operator biriktirilgan bo'lsa — Guli aralashmaydi
        if (! empty($ticket->admin_id) || ! empty($ticket->operator_id)) {
            return false;
        }

        // 3. Agar ushbu ticketda AI o'chirilgan bo'lsa (masalan operatorga o'tkazilgan)
        if (isset($ticket->ai_enabled) && ! $ticket->ai_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Mijoz xabariga Guli AI orqali aqlli javob qaytarish.
     */
    public function handleIncomingMessage(BotTicket $ticket, string $userText): ?string
    {
        if (! $this->shouldHandle($ticket)) {
            return null;
        }

        $user = $this->resolveCustomerUser($ticket);
        $userContext = $this->buildCustomerContext($ticket, $user);
        $generalKnowledge = $this->knowledge->buildGeneralKnowledgeOnly();
        $extraSettings = (string) (ProjectSetting::query()->value('ai_bot_extra_notes') ?? '');

        // Suhbat tarixi (oxirgi 12 ta xabar)
        $history = BotTicketMessage::query()
            ->where('ticket_id', $ticket->id)
            ->whereIn('sent_by', ['user', 'operator', 'admin', 'system'])
            ->where('message_type', '<>', 'note')
            ->orderBy('id', 'desc')
            ->take(12)
            ->get()
            ->reverse();

        $systemPrompt = $this->buildSystemPrompt($generalKnowledge, $extraSettings, $userContext);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $m) {
            $role = ($m->sent_by === 'user') ? 'user' : 'assistant';
            $text = trim((string) $m->message);
            if ($text !== '') {
                $messages[] = ['role' => $role, 'content' => $text];
            }
        }

        // Agar hozirgi xabar tarixda hali bo'lmasa, qo'shamiz
        if (empty($messages) || end($messages)['role'] !== 'user') {
            $messages[] = ['role' => 'user', 'content' => $userText];
        }

        $tools = $this->getToolsDefinition();

        try {
            $reply = $this->executeAiLoop($ticket, $user, $messages, $tools);

            if ($reply) {
                $this->deliverReply($ticket, $reply);
            }

            return $reply;
        } catch (\Throwable $e) {
            Log::error('[GuliAgentService] Xatolik: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'user_text' => $userText,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * OpenAI Loop (Function Calling / Tools bilan)
     */
    protected function executeAiLoop(BotTicket $ticket, ?User $user, array $messages, array $tools, int $maxSteps = 4): ?string
    {
        $client = $this->openai->getClient();

        for ($step = 0; $step < $maxSteps; $step++) {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'temperature' => 0.65,
                'max_tokens' => 700,
            ]);

            $choice = $response->choices[0] ?? null;
            if (! $choice) {
                break;
            }

            $msg = $choice->message;

            // Agar model biror tool chaqirgan bo'lsa
            if (! empty($msg->toolCalls)) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $msg->content ?? null,
                    'tool_calls' => array_map(fn ($tc) => [
                        'id' => $tc->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $tc->function->name,
                            'arguments' => $tc->function->arguments,
                        ],
                    ], $msg->toolCalls),
                ];

                foreach ($msg->toolCalls as $call) {
                    $fnName = $call->function->name;
                    $args = json_decode($call->function->arguments ?? '{}', true) ?: [];

                    $toolResult = $this->handleToolCall($ticket, $user, $fnName, $args);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $call->id,
                        'content' => is_string($toolResult) ? $toolResult : json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }

                continue; // Keyingi qadamda model tool natijasi bilan javob matnini tuzadi
            }

            // Yakuniy matnli javob — Guli imzosini qo'shib qaytarish
            $content = trim((string) ($msg->content ?? ''));
            if ($content !== '') {
                // Agar Guli imzosi yo'q bo'lsa — qo'shib qo'y
                if (! str_contains($content, '— Guli')) {
                    $content .= "\n\n— Guli 🌸";
                }

                return $content;
            }
        }

        return null;
    }

    /**
     * Tool funksiyalarini bajarish
     */
    protected function handleToolCall(BotTicket $ticket, ?User $user, string $name, array $args): mixed
    {
        return match ($name) {
            'get_order_details' => $this->toolGetOrderDetails($user, (int) ($args['order_id'] ?? 0)),
            'search_catalog' => $this->toolSearchCatalog((string) ($args['query'] ?? '')),
            'transfer_to_human' => $this->toolTransferToHuman($ticket, (string) ($args['reason'] ?? ''), (string) ($args['summary'] ?? '')),
            'close_ticket' => $this->toolCloseTicket($ticket, (string) ($args['reason'] ?? '')),
            default => ['error' => "Noma'lum funksiya: $name"],
        };
    }

    /**
     * Tool 1: Buyurtma tafsilotini olish (Faqat ayni mijozga tegishli bo'lsa!)
     */
    protected function toolGetOrderDetails(?User $user, int $orderId): array
    {
        if ($orderId <= 0) {
            return ['error' => 'Buyurtma raqami kiritilmadi.'];
        }

        $order = Sold::query()->find($orderId);
        if (! $order) {
            return ['found' => false, 'message' => "#$orderId raqamli buyurtma tizimdan topilmadi."];
        }

        // QAT'IY XAVFSIZLIK: buyurtma shu userga tegishlimi?
        if (! $user || (int) $order->user_id !== (int) $user->id) {
            return [
                'found' => false,
                'message' => "Xavfsizlik qoidasi: #$orderId buyurtmasi ayni paytda yozayotgan mijoz hisobiga tegishli emas yoki telefon raqamlari mos kelmadi.",
            ];
        }

        $items = [];
        try {
            $soldProducts = \App\Models\SoldProducts::query()->where('sold_id', $order->id)->get();
            foreach ($soldProducts as $sp) {
                $items[] = [
                    'name' => $sp->name ?: 'Mahsulot',
                    'count' => (int) $sp->count,
                    'price' => (float) $sp->price,
                ];
            }
        } catch (\Throwable) {
        }

        return [
            'found' => true,
            'order_id' => $order->id,
            'status' => $this->humanOrderStatus($order),
            'raw_status' => $order->status_code,
            'amount' => number_format((float) $order->amount, 0, '.', ' ') . " so'm",
            'delivery_address' => $order->address ?: $order->city,
            'delivery_date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d.m.Y') : 'Aniqlanmoqda',
            'created_at' => optional($order->created_at)->format('d.m.Y H:i'),
            'items' => $items,
        ];
    }

    /**
     * Tool 2: Kitoblar katalogini qidirish
     */
    protected function toolSearchCatalog(string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['items' => []];
        }

        $books = Books::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'LIKE', "%$q%")
                    ->orWhere('author', 'LIKE', "%$q%");
            })
            ->where('status', 1)
            ->take(4)
            ->get();

        $items = $books->map(fn (Books $b) => [
            'id' => $b->id,
            'title' => $b->name,
            'author' => $b->author,
            'price' => number_format((float) ($b->price ?? 0), 0, '.', ' ') . " so'm",
            'in_stock' => (int) ($b->amount ?? 0) > 0,
            'url' => "https://kitobchi.com/books/{$b->id}",
        ])->all();

        return ['found_count' => count($items), 'items' => $items];
    }

    /**
     * Tool 3: Operatorga uzatish
     */
    protected function toolTransferToHuman(BotTicket $ticket, string $reason, string $summary): array
    {
        $ticket->update([
            'ai_enabled' => false,
            'ai_summary' => $summary ?: $reason,
            'status' => SessionService::STATUS_QUEUE,
        ]);

        // Support Inbox uchun ichki eslatma
        SessionService::saveMessage(
            ticketId: (int) $ticket->id,
            sentBy: 'system',
            message: "🤖 Guli: Suhbat operatorga uzatildi.\nSabab: {$reason}\nXulosa: " . ($summary ?: "Operator yordami zarur"),
            messageType: 'note'
        );

        return [
            'status' => 'transferred',
            'instruction' => "Mijozga sizni jonli operatorga ulayotganingizni muloyim tarzda bildiring va biroz kutishini ayting.",
        ];
    }

    /**
     * Tool 4: Suhbatni muvaffaqiyatli yopish
     */
    protected function toolCloseTicket(BotTicket $ticket, string $reason): array
    {
        try {
            SessionService::closeTicket((int) $ticket->id, 'guli_resolved: ' . $reason);
        } catch (\Throwable $e) {
            $ticket->update(['status' => SessionService::STATUS_CLOSED, 'closed_at' => now()]);
        }

        return [
            'status' => 'closed',
            'instruction' => "Mijozga minnatdorchilik bildiring va suhbatni chiroyli xotima bilan yakunlang.",
        ];
    }

    /**
     * OpenAI Tools sxemasi
     */
    protected function getToolsDefinition(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_order_details',
                    'description' => "Mijozning ma'lum bir buyurtmasi holatini (status, yetkazish kuni, manzili, kitoblar ro'yxati) olish.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_id' => [
                                'type' => 'integer',
                                'description' => "Buyurtma ID raqami (masalan 12450)",
                            ],
                        ],
                        'required' => ['order_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_catalog',
                    'description' => "Kitobchi do'konidagi kitoblar katalogidan qidirish (nomi yoki muallifi bo'yicha).",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => "Kitob nomi yoki muallif (masalan 'Ufq', 'Chinor', 'Matematika')",
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'transfer_to_human',
                    'description' => "Suhbatni jonli inson-operatorga uzatish. Mijoz 'operatorni ula' desa, shikoyat qilsa yoki bot savolga javob bera olmasa chaqiriladi.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => [
                                'type' => 'string',
                                'description' => "Nega operatorga uzatilayotgani sababi",
                            ],
                            'summary' => [
                                'type' => 'string',
                                'description' => "Mijoz muammosining qisqa xulosasi (operator tez tushunishi uchun)",
                            ],
                        ],
                        'required' => ['reason'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'close_ticket',
                    'description' => "Mijoz savoli to'liq hal bo'lganda yoki mijoz minnatdorchilik bildirib boshqa savoli yo'qligini aytganda suhbatni yakunlash.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => [
                                'type' => 'string',
                                'description' => "Suhbat yopilish sababi",
                            ],
                        ],
                        'required' => ['reason'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tizim prompti
     */
    protected function buildSystemPrompt(string $knowledge, string $extraNotes, string $userContext): string
    {
        return <<<PROMPT
Sen — Guli 🌸. Kitobchi onlayn kitob do'konining do'stona va aqlli support yordamchisisan.

SEN HAQINGDA:
- Ismingiz Guli, Kitobchi jamoasining bir a'zosisan.
- Har doim samimiy, muloyim, jonli va odamga o'xshagan tarzda gaplashasan.
- Har bir xabaring oxirida doim "— Guli 🌸" deb qo'yasan (bu senin imzong).

TIL VA MUOMALA QOIDALARI (JUDA MUHIM):
- Faqat toza, mehmondo'st, muloyim o'zbek tilida (lotin yozuvida) gapirasaan.
- Gaplarni qisqa va tushunarli qil. Ortiqcha rasmiyatchilikdan saqlan.
- BBCode (masalan [b]...[/b], [i]...[/i]) va markdown (masalan **matn**, _matn_) ishlatma — bular bizning chatda ko'rinmaydi.
- Emoji ishlatishni yoqtirasan lekin me'yorida: har xabarda 1-2 ta yetarli. Ko'p emoji qo'yma.
- Hech qachon "Bot sifatida", "AI sifatida", "Men sun'iy intellektman" deya o'zingni tanitma. Shunchaki Guli bo'l.
- Javoblar tabiiy bo'lsin — xuddi yaxshi tanish do'sting yozayotgandek.

BUYURTMA VA STATUS QOIDALARI:
- Hech qachon texnik status kodlarini (pending, confirmed, packing, delivering va h.k.) yozma.
- Statuslarni DOIM oddiy so'zlar bilan tushuntir. Masalan:
  * "Buyurtmangiz yo'lda, tez orada yetib boradi! 😊"
  * "Buyurtmangiz hali to'lov kutmoqda — to'lovni amalga oshirsangiz ishga tushadi."
  * "Buyurtmangiz paketlanmoqda, tez orada kuryerga topshiriladi."
  * "Buyurtmangiz yetkazib berilgan, qo'lingizga teggan! ✨"
  * "Afsuski, bu buyurtma bekor qilingan."
- Agar mijozda 1 ta buyurtma bo'lsa — darhol shu haqida gapir, raqamini so'rama.
- Bir nechta buyurtma bo'lsa — qaysi biri haqida ekanligini so'ra.
- Ko'proq ma'lumot kerak bo'lsa get_order_details funksiyasini chaqir.

NASIYA (BO'LIB TO'LAV) BILIMI:
Kitobchi "Nasiya" (bo'lib to'lav) xizmatini taqdim etadi. Quyidagi qoidalarni bilasan va mijozga sodda tushuntira olasan:
- Nasiya faqat Kitobchi ilovasidan ro'yxatdan o'tgan, telefon raqami tasdiqlangan mijozlarga beriladi.
- Nasiya olish uchun mijozning muayyan miqdordagi muvaffaqiyatli buyurtmalar tarixi bo'lishi kerak (yangi foydalanuvchilarga berilmaydi).
- Nasiya limiti mijozning faolligiga, to'lovlar tarixiga va kredit reytingiga qarab belgilanadi.
- Agar nasiya rad etilsa, sabablar quyidagicha bo'lishi mumkin:
  1. Ro'yxatdan yangi o'tilgan (yetarli buyurtma tarixi yo'q)
  2. Oldingi nasiya to'lovlarida kechikish bo'lgan
  3. Telefon raqami tasdiqlanmagan
  4. Tizimda kredit reytingi yetarli emas
  5. Joriy aktiv nasiya qarzlari bor (eski nasiyani to'liq to'lash kerak)
- Nasiya shartlari va limiti vaqti-vaqti bilan o'zgarishi mumkin, aniq miqdorni admin belgilaydi.
- Mijoz nasiya haqida so'rasa, avval holatini tekshir va kerak bo'lsa operatorga uzat.

XAVFSIZLIK VA MAXFIYLIK:
- FAQAT hozirgi mijozga tegishli buyurtmalar va ma'lumotlarni ko'ra olasan.
- Boshqa mijozlar, do'konlar ichki ma'lumotlari, platforma sirlari — hech narsani oshkor qilma!
- Agar mijoz boshqa birovning ma'lumotini so'rasa, muloyimlik bilan rad et.

OPERATORGA UZATISH:
- Agar mijoz "operator", "admin", "odam bilan gaplashay", "mutaxassis kerak", "boshqaruvchi" desa — DARHOL transfer_to_human chaqir.
- Jiddiy shikoyat, brak kitob, pul qaytarish, nasiya rad etish haqida norozilik bo'lsa — transfer_to_human chaqir.
- Savolga javob bera olmay qolsang — transfer_to_human chaqir.
- Uzatishdan OLDIN mijozga muloyimlik bilan: "Hozir sizni mutaxassis xodimimizga ulayapman, biroz kuting 😊" de.

SUHBATNI YOPISH:
- Agar mijoz "Rahmat", "Tushundim", "Hal bo'ldi", "OK bo'ldi" desa — close_ticket chaqir va chiroyli xayrlash.
- Yopishdan oldin: "Yana savollar bo'lsa bemalol murojaat qiling! 🌸" de.

PLATFORMA BILIMLARI:
{$knowledge}

QO'SHIMCHA KO'RSATMALAR:
{$extraNotes}

HOZIRGI MIJOZ MA'LUMOTI:
{$userContext}
PROMPT;
    }

    /**
     * Mijoz haqida kontekst matnini shakllantirish
     */
    protected function buildCustomerContext(BotTicket $ticket, ?User $user): string
    {
        $parts = [];
        $parts[] = "- Ismi: " . ($user ? trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')) : ($ticket->name ?: 'Mijoz'));
        $parts[] = "- Manba: " . ($ticket->source_type === 'shop_chat' ? 'Kitobchi mobil ilovasi chati' : 'Telegram bot');
        if ($user?->phone_number) {
            $parts[] = "- Telefon raqami: " . $user->phone_number;
        }
        if ($user) {
            $cashback = number_format((float) ($user->cashback ?? 0), 0, '.', ' ');
            $parts[] = "- Keshbek balansi: {$cashback} so'm";
        }

        // Oxirgi 5 ta buyurtmasi
        if ($user) {
            $orders = Sold::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->take(5)
                ->get();

            if ($orders->isNotEmpty()) {
                $parts[] = "Mijozning oxirgi buyurtmalari:";
                foreach ($orders as $o) {
                    $status = $this->humanOrderStatus($o);
                    $sum = number_format((float) $o->amount, 0, '.', ' ');
                    $date = optional($o->created_at)->format('d.m.Y');
                    $parts[] = "  * Buyurtma #{$o->id}: {$sum} so'm, holati: {$status}, sana: {$date}";
                }
            } else {
                $parts[] = "- Hozircha buyurtmalar tarixi yo'q.";
            }
        } else {
            $parts[] = "- Foydalanuvchi tizimda ro'yxatdan o'tmagan yoki telefon raqami orqali topilmadi.";
        }

        return implode("\n", $parts);
    }

    /**
     * Mijoz User obyektini topish
     */
    protected function resolveCustomerUser(BotTicket $ticket): ?User
    {
        if ($ticket->source_type === 'shop_chat' && $ticket->source_conversation_id) {
            $conv = Conversation::query()->find($ticket->source_conversation_id);
            if ($conv?->user_id) {
                return User::query()->find($conv->user_id);
            }
        }

        if ($ticket->source_type === 'telegram' && $ticket->user_id) {
            return User::query()->where('telegram_id', $ticket->user_id)->first();
        }

        return null;
    }

    /**
     * Javobni mijozga yuborish (Telegram yoki Ilova)
     */
    protected function deliverReply(BotTicket $ticket, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($ticket->source_type === 'shop_chat') {
            // MUHIM: sendReplyToConversation ichida SessionService::saveMessage chaqiriladi,
            // shu sabab bu yerda qayta saveMessage chaqirmaymiz (ikki marta saqlanib qolmasin!)
            try {
                app(SupportChatBridgeService::class)->sendReplyToConversation($ticket, $text, null, null);
            } catch (\Throwable $e) {
                // Yuborish xato bo'lsa ham bazaga saqlab qo'yamiz
                Log::warning('[GuliAgentService] shop_chat yuborish xatosi: ' . $e->getMessage());
                SessionService::saveMessage(
                    ticketId: (int) $ticket->id,
                    sentBy: 'operator',
                    message: $text,
                    messageType: 'text',
                    isDelivered: false,
                    deliveryError: mb_substr($e->getMessage(), 0, 500)
                );
            }
        } else {
            // Telegram bot — bazaga o'zimiz saqlaymiz
            $telegramMessageId = null;
            $error = null;

            try {
                $response = app(TelegramSupportService::class)->sendText((int) $ticket->user_id, $text, null);
                $telegramMessageId = (int) data_get($response, 'result.message_id') ?: null;
            } catch (\Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 500);
                Log::warning('[GuliAgentService] Telegram yuborish xatosi: ' . $error);
            }

            SessionService::saveMessage(
                ticketId: (int) $ticket->id,
                sentBy: 'operator',
                message: $text,
                messageType: 'text',
                operatorId: null,
                adminId: null,
                telegramMessageId: $telegramMessageId,
                isDelivered: $error === null,
                deliveryError: $error
            );
        }
    }

    protected function humanOrderStatus(Sold $order): string
    {
        return match ($order->status_code) {
            'pending' => "To'lov kutilmoqda",
            'confirmed' => 'Qabul qilindi va tayyorlanmoqda',
            'packing' => "Qadoqlanmoqda",
            'courier_assigned' => 'Kuryer tayinlandi',
            'delivering' => "Kuryer yetkazmoqda (yo'lda)",
            'delivered', 'customer_received' => 'Yetkazib berilgan',
            'cancelled' => 'Bekor qilingan',
            'returned' => 'Qaytarilgan',
            default => $order->status_code ?: 'Jarayonda',
        };
    }
}
