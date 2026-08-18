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
        return DB::table('bot_operator_stats')->where('operator_id', $operatorId)->first()
            ?? (object) ['handled' => 0, 'closed' => 0, 'total_rated' => 0, 'avg_rating' => 0];
    }

    // ─── TEZKOR JAVOBLAR (QUICK REPLIES / CANNED RESPONSES) ───────────────────

    public static function getQuickReplies(): array
    {
        return [
            'greeting' => [
                'title' => '👋 Salomlashish',
                'text'  => "Assalomu alaykum! Kitobchi mijozlarni qo'llab-quvvatlash xizmati. Sizga qanday yordam bera olaman?",
            ],
            'order_status' => [
                'title' => '📦 Buyurtma holati',
                'text'  => "Buyurtmangiz holatini tekshirish uchun iltimos buyurtma raqamingizni yoki ro'yxatdan o'tgan telefon raqamingizni yuboring.",
            ],
            'payment' => [
                'title' => '💳 To\'lov ma\'lumoti',
                'text'  => "To'lov Click, Payme yoki karta orqali amalga oshiriladi. Agar to'lovda muammo bo'lsa, chek skrinshotini yuborishingiz mumkin.",
            ],
            'delivery' => [
                'title' => '🚚 Yetkazib berish',
                'text'  => "Toshkent shahri bo'yicha yetkazib berish 24 soat ichida, viloyatlarga 2-3 ish kuni ichida amalga oshiriladi.",
            ],
            'wait' => [
                'title' => '⏳ Kuting',
                'text'  => "Ma'lumotlaringizni tekshirmoqdaman, iltimos bir necha daqiqa kuting...",
            ],
            'app' => [
                'title' => '📱 Ilova havolasi',
                'text'  => "Kitobchi mobil ilovasini yuklab oling:\niOS: App Store\nAndroid: Google Play Store",
            ],
            'farewell' => [
                'title' => '🙏 Xayrlashuv',
                'text'  => "Murojaatingiz uchun rahmat! Agar boshqa savollar bo'lsa, bemalol murojaat qiling. Kutingiz hayrli o'tsin!",
            ],
        ];
    }

    // ─── YORDAMCHILAR ─────────────────────────────────────────────────────────

    public static function formatUser(?string $name, ?string $username, int $id): string
    {
        $parts = [];
        if ($name)     $parts[] = e($name);
        if ($username) $parts[] = "@" . e($username);
        if (!$parts)   $parts[] = "ID: <code>$id</code>";
        return implode(' · ', $parts);
    }
}
