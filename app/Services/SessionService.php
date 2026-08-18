<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BotTicketMessage;
use App\Models\BotTicket;
use App\Models\BotOperator;

class SessionService
{
    public const STATUS_QUEUE  = 'queue';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RATED  = 'rated';

    public const OP_ONLINE  = 'online';
    public const OP_BUSY    = 'busy';
    public const OP_OFFLINE = 'offline';
    public const OP_BREAK   = 'break';

    // ─── TICKETLAR ────────────────────────────────────────────────────────────

    public static function createTicket(
        int $userId,
        string $firstMessage,
        ?string $username = null,
        ?string $name = null,
        string $sourceType = 'bot',
        ?int $sourceConversationId = null
    ): object {
        $data = [
            'user_id'                => $userId,
            'source_type'            => $sourceType,
            'source_conversation_id' => $sourceConversationId,
            'username'               => $username,
            'name'                   => $name,
            'status'                 => self::STATUS_QUEUE,
            'first_msg'              => mb_substr($firstMessage, 0, 1000),
            'created_at'             => now(),
            'updated_at'             => now(),
        ];

        Log::info("[SessionService] createTicket urinish", ['user_id' => $userId, 'data' => $data]);

        try {
            $id = DB::table('bot_tickets')->insertGetId($data);
            $ticket = DB::table('bot_tickets')->where('id', $id)->first();

            if ($ticket) {
                Log::info("[SessionService] ticket yaratildi", ['ticket_id' => $ticket->id, 'user_id' => $userId]);
            }

            return $ticket ?: (object) ['id' => $id, 'user_id' => $userId, 'status' => self::STATUS_QUEUE];
        } catch (\Throwable $e) {
            Log::error("[SessionService] createTicket xatosi", [
                'message' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    public static function getTicket(int $ticketId): ?object
    {
        return DB::table('bot_tickets')->where('id', $ticketId)->first();
    }

    public static function updateTicket(int $ticketId, array $data): void
    {
        try {
            DB::table('bot_tickets')
                ->where('id', $ticketId)
                ->update(array_merge($data, ['updated_at' => now()]));
        } catch (\Throwable $e) {
            Log::error("[SessionService] updateTicket xatosi", ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
        }
    }

    public static function getUserActiveTicket(int $userId): ?object
    {
        return DB::table('bot_tickets')
            ->where('user_id', $userId)
            ->whereIn('status', [self::STATUS_QUEUE, self::STATUS_ACTIVE])
            ->orderByDesc('id')
            ->first();
    }

    public static function getOperatorActiveTicket(int $operatorId): ?object
    {
        return DB::table('bot_tickets')
            ->where('operator_id', $operatorId)
            ->where('status', self::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Operator Telegramda biror xabarga "Reply" (javob) qilganda
     * o'sha xabar qaysi ticketga tegishli ekanligini aniqlash.
     */
    public static function findTicketByReplyMessage(int $operatorChatId, int $repliedMessageId): ?object
    {
        // 1. bot_ticket_messages jadvalidan operatorga yuborilgan yoki yozilgan telegram_message_id ni qidirish
        $msg = DB::table('bot_ticket_messages')
            ->where('telegram_message_id', $repliedMessageId)
            ->first();

        if ($msg) {
            return self::getTicket((int) $msg->ticket_id);
        }

        // 2. Agar topilmasa, operatorning faol ticketini qaytarish
        return self::getOperatorActiveTicket($operatorChatId);
    }

    public static function getQueue(): array
    {
        return DB::table('bot_tickets')
            ->where('status', self::STATUS_QUEUE)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public static function getQueuePosition(int $ticketId): int
    {
        $ticket = self::getTicket($ticketId);
        if (!$ticket) return 1;

        $position = DB::table('bot_tickets')
            ->where('status', self::STATUS_QUEUE)
            ->where('created_at', '<=', $ticket->created_at)
            ->count();

        return max(1, $position);
    }

    /**
     * Atomik tarzda operatorga ticket biriktirish (Race condition himoyasi bilan).
     */
    public static function assignOperator(int $ticketId, int $operatorId): bool
    {
        Log::info("[SessionService] assignOperator urinish", ['ticket_id' => $ticketId, 'operator_id' => $operatorId]);

        return DB::transaction(function () use ($ticketId, $operatorId) {
            $ticket = DB::table('bot_tickets')
                ->where('id', $ticketId)
                ->lockForUpdate()
                ->first();

            if (!$ticket || $ticket->status !== self::STATUS_QUEUE) {
                Log::warning("[SessionService] Ticket allaqachon olingan yoki mavjud emas", ['ticket_id' => $ticketId]);
                return false;
            }

            DB::table('bot_tickets')
                ->where('id', $ticketId)
                ->update([
                    'status'      => self::STATUS_ACTIVE,
                    'operator_id' => $operatorId,
                    'updated_at'  => now(),
                ]);

            DB::table('bot_operators')->updateOrInsert(
                ['telegram_id' => $operatorId],
                ['status' => self::OP_BUSY, 'updated_at' => now()]
            );

            self::incrementStat($operatorId, 'handled');
            return true;
        });
    }

    public static function closeTicket(int $ticketId, string $reason = ''): void
    {
        Log::info("[SessionService] closeTicket", ['ticket_id' => $ticketId, 'reason' => $reason]);
        $ticket = self::getTicket($ticketId);
        if (!$ticket) return;

        self::updateTicket($ticketId, [
            'status'       => self::STATUS_CLOSED,
            'close_reason' => $reason,
            'closed_at'    => now(),
        ]);

        if ($ticket->operator_id) {
            DB::table('bot_operators')
                ->where('telegram_id', $ticket->operator_id)
                ->update([
                    'status'     => self::OP_ONLINE,
                    'updated_at' => now(),
                ]);
            self::incrementStat((int) $ticket->operator_id, 'closed');
        }

        // Agar bu shop_chat (veb/ilova) bo'lsa, conversationga bildirishnoma yuborish
        if ($ticket->source_type === 'shop_chat') {
            try {
                $botTicketModel = \App\Models\BotTicket::find($ticketId);
                if ($botTicketModel) {
                    app(SupportChatBridgeService::class)->notifyConversationTicketClosed($botTicketModel, $reason);
                }
            } catch (\Throwable $e) {
                Log::warning("[SessionService] shop_chat close bildirishnoma xatosi: " . $e->getMessage());
            }
        }
    }

    /**
     * Barcha ochiq va navbatdagi ticketlarni bir vaqtda yopish (Admin uchun).
     */
    public static function closeAllOpenTickets(string $reason = 'admin_bulk_closed'): int
    {
        Log::info("[SessionService] closeAllOpenTickets boshlandi", ['reason' => $reason]);

        $count = DB::table('bot_tickets')
            ->whereIn('status', [self::STATUS_QUEUE, self::STATUS_ACTIVE])
            ->count();

        if ($count === 0) return 0;

        DB::table('bot_tickets')
            ->whereIn('status', [self::STATUS_QUEUE, self::STATUS_ACTIVE])
            ->update([
                'status'       => self::STATUS_CLOSED,
                'close_reason' => $reason,
                'closed_at'    => now(),
                'updated_at'   => now(),
            ]);

        // Barcha band bo'lgan operatorlarni qayta online qilish
        DB::table('bot_operators')
            ->where('status', self::OP_BUSY)
            ->update([
                'status'     => self::OP_ONLINE,
                'updated_at' => now(),
            ]);

        return $count;
    }

    // ─── ILOVALAR VA XABARLAR ──────────────────────────────────────────────────

    public static function saveAttachment(
        int $ticketId,
        string $fileId,
        string $fileType,
        string $sentBy,
        ?string $fileName = null,
        ?int $fileSize = null
    ): void {
        try {
            DB::table('bot_ticket_attachments')->insert([
                'ticket_id'  => $ticketId,
                'file_id'    => $fileId,
                'file_type'  => $fileType,
                'file_name'  => $fileName,
                'file_size'  => $fileSize,
                'sent_by'    => $sentBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] saveAttachment xatosi", ['error' => $e->getMessage()]);
        }
    }

    public static function saveMessage(
        int $ticketId,
        string $sentBy,
        ?string $message,
        string $messageType = 'text',
        ?int $operatorId = null,
        ?int $adminId = null,
        ?int $telegramActorId = null,
        ?int $telegramMessageId = null,
        bool $isDelivered = true,
        ?string $deliveryError = null
    ): BotTicketMessage {
        return BotTicketMessage::create([
            'ticket_id'           => $ticketId,
            'sent_by'             => $sentBy,
            'operator_id'         => $operatorId,
            'admin_id'            => $adminId,
            'telegram_actor_id'   => $telegramActorId,
            'message_type'        => $messageType,
            'message'             => $message ? mb_substr($message, 0, 5000) : null,
            'telegram_message_id' => $telegramMessageId,
            'is_delivered'        => $isDelivered,
            'delivery_error'      => $deliveryError,
        ]);
    }

    public static function saveSystemMessage(int $ticketId, string $message): BotTicketMessage
    {
        return self::saveMessage(
            ticketId: $ticketId,
            sentBy: 'system',
            message: $message,
            messageType: 'system'
        );
    }

    public static function getTicketAttachments(int $ticketId): array
    {
        return DB::table('bot_ticket_attachments')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    // ─── OPERATORLAR VA ADMINLAR ──────────────────────────────────────────────

    public static function getOperators(): array
    {
        $configOps = array_map('intval', (array) config('nutgram.operators', []));
        $dbOps     = DB::table('bot_operators')
            ->where('is_active', 1)
            ->pluck('telegram_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        return array_values(array_unique(array_filter(array_merge($configOps, $dbOps))));
    }

    public static function getAdmins(): array
    {
        return array_values(array_unique(array_filter(array_map('intval', (array) config('nutgram.admins', [])))));
    }

    public static function isOperator(int $telegramId): bool
    {
        if (self::isAdmin($telegramId)) return true;
        $configOps = array_map('intval', (array) config('nutgram.operators', []));
        if (in_array($telegramId, $configOps)) return true;

        return DB::table('bot_operators')
            ->where('telegram_id', $telegramId)
            ->where('is_active', 1)
            ->exists();
    }

    public static function isAdmin(int $telegramId): bool
    {
        return in_array($telegramId, self::getAdmins());
    }

    public static function addOperator(int $telegramId, ?string $name = null, ?string $username = null): void
    {
        try {
            DB::table('bot_operators')->updateOrInsert(
                ['telegram_id' => $telegramId],
                [
                    'name'       => $name,
                    'username'   => $username,
                    'is_active'  => 1,
                    'status'     => self::OP_ONLINE,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error("[SessionService] addOperator xatosi", ['telegram_id' => $telegramId, 'error' => $e->getMessage()]);
        }
    }

    public static function removeOperator(int $telegramId): void
    {
        try {
            DB::table('bot_operators')
                ->where('telegram_id', $telegramId)
                ->update(['is_active' => 0, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] removeOperator xatosi", ['telegram_id' => $telegramId, 'error' => $e->getMessage()]);
        }
    }

    public static function getOperatorStatus(int $telegramId): string
    {
        $op = DB::table('bot_operators')->where('telegram_id', $telegramId)->first();
        return $op?->status ?? self::OP_ONLINE;
    }

    public static function setOperatorStatus(int $telegramId, string $status): void
    {
        DB::table('bot_operators')->updateOrInsert(
            ['telegram_id' => $telegramId],
            ['status' => $status, 'updated_at' => now()]
        );
    }

    public static function findFreeOperator(): ?int
    {
        $operators = self::getOperators();
        if (empty($operators)) return null;

        foreach ($operators as $opId) {
            $status = self::getOperatorStatus($opId);
            if ($status === self::OP_OFFLINE || $status === self::OP_BREAK) continue;

            $hasActive = DB::table('bot_tickets')
                ->where('operator_id', $opId)
                ->where('status', self::STATUS_ACTIVE)
                ->exists();

            if (!$hasActive) {
                return $opId;
            }
        }
        return null;
    }

    // ─── STATISTIKA VA BAHOLASH ───────────────────────────────────────────────

    public static function incrementStat(int $operatorId, string $key): void
    {
        DB::table('bot_operator_stats')->updateOrInsert(
            ['operator_id' => $operatorId],
            [$key => DB::raw("$key + 1"), 'updated_at' => now()]
        );
    }

    public static function addRating(int $operatorId, int $score): void
    {
        $stats = DB::table('bot_operator_stats')->where('operator_id', $operatorId)->first();
        if ($stats) {
            $total = $stats->total_rated + 1;
            $avg   = (($stats->avg_rating * $stats->total_rated) + $score) / $total;
            DB::table('bot_operator_stats')->where('operator_id', $operatorId)->update([
                'total_rated' => $total,
                'avg_rating'  => round($avg, 2),
                'updated_at'  => now(),
            ]);
        } else {
            DB::table('bot_operator_stats')->insert([
                'operator_id' => $operatorId,
                'total_rated' => 1,
                'avg_rating'  => $score,
                'updated_at'  => now(),
            ]);
        }
    }

    public static function getStats(int $operatorId): object
    {
        // 1. bot_tickets jadvalidan jonli hisoblash
        $handled = DB::table('bot_tickets')->where('operator_id', $operatorId)->count();
        $closed  = DB::table('bot_tickets')->where('operator_id', $operatorId)->whereIn('status', [self::STATUS_CLOSED, self::STATUS_RATED])->count();
        $active  = DB::table('bot_tickets')->where('operator_id', $operatorId)->where('status', self::STATUS_ACTIVE)->count();

        $todayClosed = DB::table('bot_tickets')
            ->where('operator_id', $operatorId)
            ->whereIn('status', [self::STATUS_CLOSED, self::STATUS_RATED])
            ->whereDate('closed_at', now()->toDateString())
            ->count();

        $ratedCount = DB::table('bot_tickets')->where('operator_id', $operatorId)->whereNotNull('rating')->count();
        $avgRating  = DB::table('bot_tickets')->where('operator_id', $operatorId)->whereNotNull('rating')->avg('rating') ?: 0;

        // 2. bot_operator_stats jadvalidagi ma'lumotlarni hisobga olish
        $statsRow = DB::table('bot_operator_stats')->where('operator_id', $operatorId)->first();
        if ($statsRow) {
            $handled = max($handled, (int) $statsRow->handled);
            $closed  = max($closed, (int) $statsRow->closed);
            if ((int) $statsRow->total_rated > $ratedCount) {
                $ratedCount = (int) $statsRow->total_rated;
                $avgRating  = (float) $statsRow->avg_rating;
            }
        }

        return (object) [
            'handled'      => $handled,
            'closed'       => $closed,
            'active'       => $active,
            'today_closed' => $todayClosed,
            'total_rated'  => $ratedCount,
            'avg_rating'   => round((float) $avgRating, 2),
        ];
    }

    public static function getTicketUserMessages(int $ticketId): array
    {
        return DB::table('bot_ticket_messages')
            ->where('ticket_id', $ticketId)
            ->where('sent_by', 'user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    // ─── TEZKOR JAVOBLAR (QUICK REPLIES / CANNED RESPONSES) ───────────────────

    public static function getQuickReplyCategories(): array
    {
        return [
            'payment'  => ['title' => '💳 To\'lov va Karta', 'emoji' => '💳'],
            'delivery' => ['title' => '🚚 Yetkazib berish',  'emoji' => '🚚'],
            'order'    => ['title' => '📦 Buyurtma',         'emoji' => '📦'],
            'app'      => ['title' => '📱 Mobil ilova',      'emoji' => '📱'],
            'cashback' => ['title' => '🎁 Keshbek',          'emoji' => '🎁'],
            'info'     => ['title' => '📍 Ish vaqti/Manzil', 'emoji' => '📍'],
            'general'  => ['title' => '💬 Xushmuomalalik',   'emoji' => '💬'],
        ];
    }

    public static function getQuickReplies(?string $category = null): array
    {
        $all = [
            // 💳 To'lov va Karta
            'payment_card' => [
                'category' => 'payment',
                'title'    => '💳 Karta orqali to\'lov (Uzcard/Humo)',
                'summary'  => 'Uzcard va Humo kartalari orqali to\'lov haqida',
                'text'     => "💳 <b>To'lov usuli haqida:</b>\n\nIlovamizda yoki saytimizda <b>Uzcard</b> va <b>Humo</b> kartalaringizni bemalol ulab, to'g'ridan-to'g'ri xavfsiz to'lov qilishingiz mumkin.\n\n🔒 Barcha to'lovlar xavfsiz himoyalangan va karta ma'lumotlaringiz maxfiy saqlanadi.",
            ],
            'payment_problem' => [
                'category' => 'payment',
                'title'    => '⚠️ To\'lovda muammo',
                'summary'  => 'To\'lov o\'tmay qolganda',
                'text'     => "⚠️ <b>To'lov amalga oshmay qolgan bo'lsa:</b>\n\n1. Kartangizda SMS-xabarnoma (3DS) yoqilganligini tekshiring.\n2. Mablag' yetarli ekanligiga ishonch hosil qiling.\n3. Agar pul yechilib, buyurtma faollashmagan bo'lsa, to'lov cheki skrinshotini shu yerga yuboring — darhol tekshirib beramiz!",
            ],

            // 🚚 Yetkazib berish
            'delivery_terms' => [
                'category' => 'delivery',
                'title'    => '🚚 Yetkazib berish muddatlari',
                'summary'  => 'Toshkent va viloyatlarga yetkazish vaqtlari',
                'text'     => "🚚 <b>Yetkazib berish xizmati:</b>\n\n• <b>Toshkent shahri bo'yicha:</b> 24 soat ichida eshikkacha yetkaziladi.\n• <b>Viloyat va tuman markazlariga:</b> 2-3 ish kuni ichida ishonchli kurerlik xizmati orqali yetkaziladi.",
            ],
            'delivery_price' => [
                'category' => 'delivery',
                'title'    => '💰 Yetkazib berish narxi',
                'summary'  => 'Yetkazish narxlari va bepul yetkazish',
                'text'     => "💰 <b>Yetkazib berish narxi:</b>\n\nYetkazib berish narxi siz tanlagan manzil va buyurtma hajmiga qarab savatchada avtomatik hisoblanadi.\n\n🎁 Shuningdek, ma'lum miqdordagi xaridlar uchun bepul yetkazib berish aksiyalari mavjud.",
            ],

            // 📦 Buyurtma
            'order_tracking' => [
                'category' => 'order',
                'title'    => '🔍 Buyurtma holatini tekshirish',
                'summary'  => 'Buyurtma raqamini so\'rash',
                'text'     => "📦 <b>Buyurtmangiz holatini tekshirish uchun:</b>\n\nIltimos, <b>buyurtma raqamingizni</b> yoki ro'yxatdan o'tgan <b>telefon raqamingizni</b> yozib yuboring. Darhol tekshirib, xabar beramiz.",
            ],
            'order_cancel' => [
                'category' => 'order',
                'title'    => '❌ Buyurtmani bekor qilish',
                'summary'  => 'Buyurtmani bekor qilish tartibi',
                'text'     => "❌ <b>Buyurtmani bekor qilish:</b>\n\nBuyurtmangiz hali kurerga topshirilmagan bo'lsa, uni bekor qilishimiz mumkin. Iltimos, buyurtma raqamingizni yuboring.",
            ],

            // 📱 Mobil ilova
            'app_download' => [
                'category' => 'app',
                'title'    => '📲 Mobil ilovani yuklash',
                'summary'  => 'App Store va Google Play rasmiy havolasi',
                'text'     => "📲 <b>Kitobchi rasmiy mobil ilovasi:</b>\n\nKitoblar xarid qilish, audio kitoblarni tinglash va keshbek to'plash uchun mobil ilovamizdan foydalaning!\n\n🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">App Store orqali yuklash (iOS)</a>\n🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Google Play orqali yuklash (Android)</a>\n🌐 <a href=\"https://kitobchi.com\">Rasmiy veb-sayt: kitobchi.com</a>",
            ],

            // 🎁 Keshbek
            'cashback_info' => [
                'category' => 'cashback',
                'title'    => '🎁 Keshbek tizimi haqida',
                'summary'  => 'Keshbek qanday to\'planadi va ishlatiladi',
                'text'     => "🎁 <b>Kitobchi Keshbek tizimi:</b>\n\nHar bir amalga oshirgan xaridingizdan shaxsiy balansingizga keshbek hisoblanadi. To'plangan keshbek mablag'larini keyingi kitob xaridlaringizda chegirma sifatida to'liq ishlatishingiz mumkin!",
            ],

            // 📍 Ish tartibi
            'work_hours' => [
                'category' => 'info',
                'title'    => '⏰ Ish tartibi va aloqa',
                'summary'  => 'Qo\'llab-quvvatlash ish vaqti',
                'text'     => "⏰ <b>Ish tartibimiz:</b>\n\nMijozlarni qo'llab-quvvatlash xizmati har kuni <b>09:00 dan 22:00 gacha</b> uzluksiz xizmat ko'rsatadi.\n\nIlovamiz va saytimiz orqali buyurtmalarni esa 24/7 istalgan vaqtda berishingiz mumkin.",
            ],

            // 💬 Xushmuomalalik
            'greeting' => [
                'category' => 'general',
                'title'    => '👋 Salomlashish',
                'summary'  => 'Xush kelibsiz xabari',
                'text'     => "Assalomu alaykum! Kitobchi mijozlarni qo'llab-quvvatlash xizmati. Sizga qanday yordam bera olaman? 😊",
            ],
            'waiting' => [
                'category' => 'general',
                'title'    => '⏳ Kuting (Tekshirilmoqda)',
                'summary'  => 'Ma\'lumot tekshirilayotgani haqida',
                'text'     => "⏳ <b>Ma'lumotlaringizni tekshirmoqdaman</b>, iltimos bir necha daqiqa kuting...",
            ],
            'farewell' => [
                'category' => 'general',
                'title'    => '🙏 Xayrlashuv va tilaklar',
                'summary'  => 'Rahmat va tilak',
                'text'     => "Murojaatingiz uchun rahmat! Kitobchi bilan mutolaadan rohatlaning. Kuningiz xayrli va unumli o'tsin! 🙏📚",
            ],
        ];

        if ($category) {
            return array_filter($all, fn($item) => ($item['category'] ?? '') === $category);
        }

        return $all;
    }

    // ─── YORDAMCHILAR VA CRM INTEGRATSIYASI ────────────────────────────────────

    public static function isWorkingHours(): bool
    {
        // Toshkent vaqti (UTC+5)
        $now = now()->setTimezone('Asia/Tashkent');
        $hour = (int) $now->format('H');
        return ($hour >= 9 && $hour < 22);
    }

    public static function formatOrderStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'pending', 'a'           => '⏳ Kutilmoqda',
            'packing', 'p'           => '📦 Yig\'ilmoqda',
            'in_delivery', 'b'       => '🚚 Yetkazilmoqda',
            'delivered', 'c'         => '✅ Yetkazildi',
            'customer_received', 'd' => '✨ Qabul qilindi',
            'cancelled', 'f'         => '❌ Bekor qilingan',
            'returned', 'r'          => '↩️ Qaytarilgan',
            default                  => $status ?: 'Yangi',
        };
    }

    public static function getCustomerInfo(int $telegramId, ?string $username = null, ?string $phone = null): ?array
    {
        try {
            // 1. telegram_id bo'yicha qidirish
            $user = \App\Models\User::where('telegram_id', $telegramId)->first();

            // 2. Agar topilmasa, telegram_username yoki username bo'yicha qidirish
            if (!$user && $username) {
                $cleanUser = ltrim($username, '@');
                $user = \App\Models\User::where('telegram_username', $cleanUser)
                    ->orWhere('username', $cleanUser)
                    ->first();

                // Avtomatik bog'lab qo'yish
                if ($user && empty($user->telegram_id)) {
                    $user->update(['telegram_id' => $telegramId]);
                }
            }

            // 3. Agar topilmasa va telefon bo'lsa, telefon bo'yicha qidirish
            if (!$user && $phone) {
                $cleanPhone = preg_replace('/[^\d]/', '', $phone);
                if (strlen($cleanPhone) >= 9) {
                    $user = \App\Models\User::where('phone_number', 'LIKE', "%$cleanPhone%")->first();
                    if ($user && empty($user->telegram_id)) {
                        $user->update(['telegram_id' => $telegramId]);
                    }
                }
            }

            if (!$user) return null;

            $soldsCount = \App\Models\Sold::where('user_id', $user->id)->count();
            $totalSpent = \App\Models\Sold::where('user_id', $user->id)
                ->whereIn('status_code', ['delivered', 'customer_received'])
                ->sum('amount');

            $lastOrder = \App\Models\Sold::where('user_id', $user->id)->latest()->first();

            $lastOrderInfo = null;
            if ($lastOrder) {
                $statusVal = $lastOrder->status_code instanceof \App\Enums\OrderStatusCode
                    ? $lastOrder->status_code->value
                    : ($lastOrder->status_code ?: $lastOrder->status);

                $lastOrderInfo = [
                    'id'            => $lastOrder->id,
                    'amount'        => number_format((float) ($lastOrder->amount ?? 0), 0, '', ' ') . " so'm",
                    'status'        => self::formatOrderStatus($statusVal),
                    'delivery_type' => $lastOrder->deliveryType ?? 'Standart',
                    'date'          => \Carbon\Carbon::parse($lastOrder->created_at)->format('d.m.Y H:i'),
                ];
            }

            return [
                'user_id'      => $user->id,
                'name'         => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
                'username'     => $user->username ?: $user->telegram_username,
                'phone'        => $user->phone_number,
                'cashback'     => number_format((float) ($user->cashback ?? 0), 0, '', ' ') . " so'm",
                'total_spent'  => number_format((float) $totalSpent, 0, '', ' ') . " so'm",
                'orders_count' => $soldsCount,
                'registered'   => \Carbon\Carbon::parse($user->created_at)->format('d.m.Y'),
                'last_order'   => $lastOrderInfo,
            ];
        } catch (\Throwable $e) {
            Log::warning("[SessionService] getCustomerInfo xatosi: " . $e->getMessage());
            return null;
        }
    }

    public static function getCustomerOrders(int $telegramId, int $limit = 3): array
    {
        try {
            $user = \App\Models\User::where('telegram_id', $telegramId)->first();
            if (!$user) return [];

            return \App\Models\Sold::where('user_id', $user->id)
                ->latest()
                ->take($limit)
                ->get()
                ->map(function ($o) {
                    $statusVal = $o->status_code instanceof \App\Enums\OrderStatusCode
                        ? $o->status_code->value
                        : ($o->status_code ?: $o->status);

                    return [
                        'id'     => $o->id,
                        'amount' => number_format((float) ($o->amount ?? 0), 0, '', ' ') . " so'm",
                        'status' => self::formatOrderStatus($statusVal),
                        'date'   => \Carbon\Carbon::parse($o->created_at)->format('d.m.Y H:i'),
                    ];
                })
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function saveInternalNote(int $ticketId, int $operatorId, string $noteText): void
    {
        self::saveMessage(
            ticketId: $ticketId,
            sentBy: 'operator',
            message: $noteText,
            messageType: 'note',
            operatorId: $operatorId,
            telegramActorId: $operatorId,
            isDelivered: true
        );
    }

    public static function formatUser(?string $name, ?string $username, int $id): string
    {
        $parts = [];
        if ($name)     $parts[] = e($name);
        if ($username) $parts[] = "@" . e($username);
        $parts[] = "(ID: <code>$id</code>)";
        return implode(' ', $parts);
    }
}
