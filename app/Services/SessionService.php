<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionService
{
    public const STATUS_QUEUE  = 'queue';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_RATED  = 'rated';

    public const OP_ONLINE  = 'online';
    public const OP_BUSY    = 'busy';
    public const OP_OFFLINE = 'offline';

    // ─── TICKET ──────────────────────────────────────────────────────────────

    public static function createTicket(int $userId, string $firstMessage, ?string $username, ?string $name): object
    {
        $data = [
            'user_id'    => $userId,
            'username'   => $username,
            'name'       => $name,
            'status'     => self::STATUS_QUEUE,
            'first_msg'  => mb_substr($firstMessage, 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Log::info("[SessionService] createTicket urinish", ['user_id' => $userId, 'data' => $data]);

        try {
            $id     = DB::table('bot_tickets')->insertGetId($data);
            $ticket = DB::table('bot_tickets')->where('id', $id)->first();

            if ($ticket) {
                Log::info("[SessionService] ticket muvaffaqiyatli yaratildi", ['ticket_id' => $ticket->id, 'user_id' => $userId]);
            } else {
                Log::error("[SessionService] ticket yaratildi lekin topilmadi", ['attempted_id' => $id]);
            }

            return $ticket ?: (object) ['id' => $id, 'error' => 'topilmadi'];
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error("[SessionService] createTicket DB xatosi", [
                'message' => $e->getMessage(), 'sql' => $e->getSql(), 'bindings' => $e->getBindings(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error("[SessionService] createTicket noma'lum xato", [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    public static function getTicket(int $ticketId): ?object
    {
        $ticket = DB::table('bot_tickets')->where('id', $ticketId)->first();
        Log::debug("[SessionService] getTicket", ['ticket_id' => $ticketId, 'found' => $ticket ? 'ha' : "yo'q"]);
        return $ticket;
    }

    public static function updateTicket(int $ticketId, array $data): void
    {
        Log::info("[SessionService] updateTicket urinish", ['ticket_id' => $ticketId, 'data' => $data]);
        try {
            $affected = DB::table('bot_tickets')
                ->where('id', $ticketId)
                ->update(array_merge($data, ['updated_at' => now()]));
            Log::info("[SessionService] updateTicket natijasi", ['ticket_id' => $ticketId, 'affected_rows' => $affected]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] updateTicket xatosi", ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
        }
    }

    public static function getUserActiveTicket(int $userId): ?object
    {
        $ticket = DB::table('bot_tickets')
            ->where('user_id', $userId)
            ->whereIn('status', [self::STATUS_QUEUE, self::STATUS_ACTIVE])
            ->orderByDesc('id')
            ->first();
        Log::debug("[SessionService] getUserActiveTicket", ['user_id' => $userId, 'ticket_id' => $ticket ? $ticket->id : "yo'q"]);
        return $ticket;
    }

    public static function getOperatorActiveTicket(int $operatorId): ?object
    {
        $ticket = DB::table('bot_tickets')
            ->where('operator_id', $operatorId)
            ->where('status', self::STATUS_ACTIVE)
            ->first();
        Log::debug("[SessionService] getOperatorActiveTicket", ['operator_id' => $operatorId, 'ticket_id' => $ticket ? $ticket->id : "yo'q"]);
        return $ticket;
    }

    public static function getQueue(): array
    {
        $queue = DB::table('bot_tickets')
            ->where('status', self::STATUS_QUEUE)
            ->orderBy('created_at')
            ->get()
            ->toArray();
        Log::debug("[SessionService] getQueue", ['count' => count($queue)]);
        return $queue;
    }

    public static function getQueuePosition(int $ticketId): int
    {
        $ticket = self::getTicket($ticketId);
        if (!$ticket) {
            Log::warning("[SessionService] getQueuePosition - ticket topilmadi", ['ticket_id' => $ticketId]);
            return 0;
        }
        $position = DB::table('bot_tickets')
            ->where('status', self::STATUS_QUEUE)
            ->where('created_at', '<=', $ticket->created_at)
            ->count();
        Log::debug("[SessionService] getQueuePosition", ['ticket_id' => $ticketId, 'position' => $position]);
        return $position;
    }

    public static function assignOperator(int $ticketId, int $operatorId): void
    {
        Log::info("[SessionService] assignOperator boshlandi", ['ticket_id' => $ticketId, 'operator_id' => $operatorId]);
        try {
            self::updateTicket($ticketId, ['status' => self::STATUS_ACTIVE, 'operator_id' => $operatorId]);
            DB::table('bot_operators')
                ->where('telegram_id', $operatorId)
                ->update(['status' => self::OP_BUSY, 'updated_at' => now()]);
            self::incrementStat($operatorId, 'handled');
            Log::info("[SessionService] assignOperator muvaffaqiyatli", ['ticket_id' => $ticketId, 'operator_id' => $operatorId]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] assignOperator xatosi", ['error' => $e->getMessage()]);
        }
    }

    public static function closeTicket(int $ticketId, string $reason = ''): void
    {
        Log::info("[SessionService] closeTicket boshlandi", ['ticket_id' => $ticketId, 'reason' => $reason]);
        $ticket = self::getTicket($ticketId);
        if (!$ticket) {
            Log::warning("[SessionService] closeTicket - ticket topilmadi", ['ticket_id' => $ticketId]);
            return;
        }
        self::updateTicket($ticketId, ['status' => self::STATUS_CLOSED, 'close_reason' => $reason]);
        if ($ticket->operator_id) {
            DB::table('bot_operators')
                ->where('telegram_id', $ticket->operator_id)
                ->update(['status' => self::OP_ONLINE, 'updated_at' => now()]);
            self::incrementStat($ticket->operator_id, 'closed');
            Log::info("[SessionService] operator statusi online ga o'tkazildi", ['operator_id' => $ticket->operator_id]);
        }
    }

    // ─── ILOVALAR (Attachments) ───────────────────────────────────────────────

    /**
     * Ticket ga yuborilgan fayl/rasm/ovoz/videoni bazaga saqlaydi.
     * UserHandler va OperatorHandler handleMessage ichida chaqiriladi.
     */
    public static function saveAttachment(
        int $ticketId,
        string $fileId,
        string $fileType,
        string $sentBy,
        ?string $fileName = null,
        ?int $fileSize = null
    ): void {
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
        Log::info("[SessionService] ilova saqlandi", [
            'ticket_id' => $ticketId,
            'type'      => $fileType,
            'sent_by'   => $sentBy,
        ]);
    }

    /**
     * Ticket ga tegishli barcha ilovalarni qaytaradi.
     * Operator /history komandasi uchun ishlatiladi.
     */
    public static function getTicketAttachments(int $ticketId): array
    {
        return DB::table('bot_ticket_attachments')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    // ─── OPERATORLAR ─────────────────────────────────────────────────────────

    public static function getOperators(): array
    {
        $configOps = array_map('intval', (array) config('nutgram.operators', []));
        $dbOps     = DB::table('bot_operators')
            ->where('is_active', 1)
            ->pluck('telegram_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
        $operators = array_values(array_unique(array_merge($configOps, $dbOps)));
        Log::debug("[SessionService] getOperators", ['config_count' => count($configOps), 'db_count' => count($dbOps), 'total' => count($operators)]);
        return $operators;
    }

    public static function getAdmins(): array
    {
        $admins = array_values(array_unique(array_map('intval', (array) config('nutgram.admins', []))));
        Log::debug("[SessionService] getAdmins", ['count' => count($admins)]);
        return $admins;
    }

    public static function isOperator(int $telegramId): bool
    {
        if (self::isAdmin($telegramId)) return true;
        $configOps = array_map('intval', (array) config('nutgram.operators', []));
        if (in_array($telegramId, $configOps)) return true;
        $exists = DB::table('bot_operators')
            ->where('telegram_id', $telegramId)
            ->where('is_active', 1)
            ->exists();
        Log::debug("[SessionService] isOperator DB tekshiruvi", ['id' => $telegramId, 'exists' => $exists]);
        return $exists;
    }

    public static function isAdmin(int $telegramId): bool
    {
        $isAdmin = in_array($telegramId, self::getAdmins());
        Log::debug("[SessionService] isAdmin", ['id' => $telegramId, 'natija' => $isAdmin]);
        return $isAdmin;
    }

    public static function addOperator(int $telegramId, ?string $name = null, ?string $username = null): void
    {
        Log::info("[SessionService] addOperator urinish", ['telegram_id' => $telegramId]);
        try {
            DB::table('bot_operators')->updateOrInsert(
                ['telegram_id' => $telegramId],
                ['name' => $name, 'username' => $username, 'is_active' => 1, 'status' => self::OP_ONLINE, 'updated_at' => now(), 'created_at' => now()]
            );
            Log::info("[SessionService] addOperator muvaffaqiyatli", ['telegram_id' => $telegramId]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] addOperator xatosi", ['telegram_id' => $telegramId, 'error' => $e->getMessage()]);
        }
    }

    public static function removeOperator(int $telegramId): void
    {
        Log::info("[SessionService] removeOperator urinish", ['telegram_id' => $telegramId]);
        try {
            DB::table('bot_operators')
                ->where('telegram_id', $telegramId)
                ->update(['is_active' => 0, 'updated_at' => now()]);
            Log::info("[SessionService] removeOperator muvaffaqiyatli", ['telegram_id' => $telegramId]);
        } catch (\Throwable $e) {
            Log::error("[SessionService] removeOperator xatosi", ['telegram_id' => $telegramId, 'error' => $e->getMessage()]);
        }
    }

    public static function getOperatorStatus(int $telegramId): string
    {
        $op     = DB::table('bot_operators')->where('telegram_id', $telegramId)->first();
        $status = $op?->status ?? self::OP_ONLINE;
        Log::debug("[SessionService] getOperatorStatus", ['telegram_id' => $telegramId, 'status' => $status]);
        return $status;
    }

    public static function setOperatorStatus(int $telegramId, string $status): void
    {
        Log::info("[SessionService] setOperatorStatus", ['telegram_id' => $telegramId, 'new_status' => $status]);
        DB::table('bot_operators')
            ->where('telegram_id', $telegramId)
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    public static function findFreeOperator(): ?int
    {
        $operators = self::getOperators();
        if (empty($operators)) return null;
        foreach ($operators as $opId) {
            if (self::getOperatorStatus($opId) === self::OP_OFFLINE) continue;
            $hasActive = DB::table('bot_tickets')
                ->where('operator_id', $opId)
                ->where('status', self::STATUS_ACTIVE)
                ->exists();
            if (!$hasActive) {
                Log::info("[SessionService] bo'sh operator topildi", ['op_id' => $opId]);
                return $opId;
            }
        }
        Log::info("[SessionService] bo'sh operator topilmadi");
        return null;
    }

    // ─── STATISTIKA ──────────────────────────────────────────────────────────

    public static function incrementStat(int $operatorId, string $key): void
    {
        Log::info("[SessionService] incrementStat", ['operator_id' => $operatorId, 'key' => $key]);
        DB::table('bot_operator_stats')->updateOrInsert(
            ['operator_id' => $operatorId],
            [$key => DB::raw("$key + 1"), 'updated_at' => now()]
        );
    }

    public static function addRating(int $operatorId, int $score): void
    {
        Log::info("[SessionService] addRating boshlandi", ['operator_id' => $operatorId, 'score' => $score]);
        $stats = DB::table('bot_operator_stats')->where('operator_id', $operatorId)->first();
        if ($stats) {
            $total = $stats->total_rated + 1;
            $avg   = (($stats->avg_rating * $stats->total_rated) + $score) / $total;
            DB::table('bot_operator_stats')->where('operator_id', $operatorId)->update([
                'total_rated' => $total,
                'avg_rating'  => round($avg, 2),
                'updated_at'  => now(),
            ]);
            Log::info("[SessionService] rating yangilandi", ['new_avg' => round($avg, 2), 'total' => $total]);
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
        $stats = DB::table('bot_operator_stats')->where('operator_id', $operatorId)->first()
            ?? (object) ['handled' => 0, 'closed' => 0, 'total_rated' => 0, 'avg_rating' => 0];
        Log::debug("[SessionService] getStats", ['operator_id' => $operatorId, 'stats' => (array) $stats]);
        return $stats;
    }

    // ─── YORDAMCHILAR ────────────────────────────────────────────────────────

    public static function formatUser(?string $name, ?string $username, int $id): string
    {
        $parts = [];
        if ($name)     $parts[] = $name;
        if ($username) $parts[] = "@$username";
        if (!$parts)   $parts[] = "ID:$id";
        $formatted = implode(' · ', $parts);
        Log::debug("[SessionService] formatUser", ['id' => $id, 'result' => $formatted]);
        return $formatted;
    }
}