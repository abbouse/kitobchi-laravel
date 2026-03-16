<?php

namespace App\Handlers;

use App\Services\SessionService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class AdminHandler
{
    public static function isAdmin(Nutgram $bot, int $chatId): bool
    {
        $isAdmin = SessionService::isAdmin($chatId);
        Log::debug("[AdminHandler] isAdmin tekshiruvi", ['chat_id' => $chatId, 'natija' => $isAdmin]);
        return $isAdmin;
    }

    public static function handleStart(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        Log::info("[Admin] /start panel ochildi", ['admin_id' => $cid]);

        $operators = SessionService::getOperators();
        $queue = SessionService::getQueue();

        $onlineCount = 0;
        $busyCount = 0;
        foreach ($operators as $opId) {
            $status = SessionService::getOperatorStatus($opId);
            if ($status === SessionService::OP_BUSY) $busyCount++;
            elseif ($status !== SessionService::OP_OFFLINE) $onlineCount++;
        }

        $bot->sendMessage(
            "🔑 <b>Admin paneli</b>\n\n" .
            "👥 Operatorlar: " . count($operators) . " ta\n" .
            "🟢 Online: $onlineCount | 🟡 Band: $busyCount\n" .
            "📋 Navbat: " . count($queue) . " ta\n\n" .
            "<b>Buyruqlar:</b>\n" .
            "/operators — Operatorlar ro'yxati\n" .
            "/addop [ID] — Operator qo'shish\n" .
            "/removeop [ID] — Operatorni o'chirish\n" .
            "/queue — Navbat\n" .
            "/allstats — Umumiy statistika\n" .
            "/broadcast [matn] — Hammaga xabar",
            parse_mode: 'HTML'
        );
    }

    public static function handleHelp(Nutgram $bot): void
    {
        Log::info("[Admin] /help chaqirildi");
        self::handleStart($bot);
    }

    public static function handleRemoveOperator(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!SessionService::isAdmin($cid)) return;

        $text  = $bot->message()?->text ?? '';
        $parts = explode(' ', trim($text));
        $rmId  = isset($parts[1]) ? (int)$parts[1] : 0;

        if (!$rmId) {
            $bot->sendMessage("Foydalanish: /removeop <code>[ID]</code>", parse_mode: 'HTML');
            return;
        }

        $configOps = (array) config('nutgram.operators', []);
        if (in_array($rmId, $configOps)) {
            $bot->sendMessage("⚠️ Bu operator config faylida belgilangan. O'chirish uchun config ni o'zgartiring.", parse_mode: 'HTML');
            return;
        }

        $activeTicket = SessionService::getOperatorActiveTicket($rmId);
        if ($activeTicket) {
            SessionService::closeTicket($activeTicket->id, 'operator_removed');
            try {
                $bot->sendMessage("Murojaatingiz texnik sabablarga ko'ra yopildi. Iltimos qayta yozing.", chat_id: $activeTicket->user_id);
            } catch (\Throwable) {}
        }

        SessionService::removeOperator($rmId);

        try {
            $bot->sendMessage("Siz operator ro'yxatidan o'chirilgansiz.", chat_id: $rmId);
        } catch (\Throwable) {}

        $bot->sendMessage("✅ Operator o'chirildi: <code>$rmId</code>", parse_mode: 'HTML');
    }

    public static function handleListOperators(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!SessionService::isAdmin($cid)) return;

        $operators = SessionService::getOperators();

        if (empty($operators)) {
            $bot->sendMessage("Operatorlar ro'yxati bo'sh.\n/addop [ID] bilan qo'shing.");
            return;
        }

        $text = "👥 <b>Operatorlar</b> (" . count($operators) . " ta):\n\n";

        foreach ($operators as $opId) {
            $status  = SessionService::getOperatorStatus($opId);
            $ticket  = SessionService::getOperatorActiveTicket($opId);
            $stats   = SessionService::getStats($opId);

            $emoji = match($status) {
                SessionService::OP_BUSY    => '🟡',
                SessionService::OP_OFFLINE => '🔴',
                default                    => '🟢',
            };

            $opInfo = \Illuminate\Support\Facades\DB::table('bot_operators')
    ->where('telegram_id', $opId)->first();
$opName = $opInfo?->name ? " ({$opInfo->name})" : "";
$text .= "$emoji <code>$opId</code>{$opName}";
            if ($ticket) $text .= " — 🎫 #{$ticket->id}";
            $text .= "\n   ✅ {$stats->closed} | ⭐ {$stats->avg_rating}/5\n";
        }

        $bot->sendMessage($text, parse_mode: 'HTML');
    }

    public static function handleAllStats(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!SessionService::isAdmin($cid)) return;

        $operators   = SessionService::getOperators();
        $queue       = SessionService::getQueue();
        $totalTickets = \Illuminate\Support\Facades\DB::table('bot_tickets')->count();

        $text  = "📊 <b>Umumiy statistika</b>\n\n";
        $text .= "🎫 Jami ticketlar: $totalTickets\n";
        $text .= "📋 Navbatda: " . count($queue) . "\n\n";
        $text .= "<b>Operatorlar:</b>\n";

        $totalClosed = 0;
        foreach ($operators as $opId) {
            $stats       = SessionService::getStats($opId);
            $totalClosed += $stats->closed;
            $status       = SessionService::getOperatorStatus($opId);
            $emoji        = match($status) {
                SessionService::OP_BUSY    => '🟡',
                SessionService::OP_OFFLINE => '🔴',
                default                    => '🟢',
            };
            $text .= "$emoji <code>$opId</code>: {$stats->closed} ✅ | ⭐{$stats->avg_rating}\n";
        }

        $text .= "\n<b>Jami yopilgan:</b> $totalClosed";
        $bot->sendMessage($text, parse_mode: 'HTML');
    }

    public static function handleBroadcast(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!SessionService::isAdmin($cid)) return;

        $text = $bot->message()?->text ?? '';
        $msg  = trim(preg_replace('/^\/broadcast\s*/i', '', $text));

        if (!$msg) {
            $bot->sendMessage("Foydalanish: /broadcast <code>Xabar matni</code>", parse_mode: 'HTML');
            return;
        }

        $userIds = \Illuminate\Support\Facades\DB::table('bot_tickets')
            ->distinct()
            ->pluck('user_id');

        $sent = 0; $failed = 0;
        foreach ($userIds as $uid) {
            try {
                $bot->sendMessage("📢 " . $msg, chat_id: $uid);
                $sent++;
                usleep(50000);
            } catch (\Throwable) {
                $failed++;
            }
        }

        $bot->sendMessage("✅ Broadcast tugadi.\n📤 Yuborildi: $sent\n❌ Xato: $failed");
    }

    public static function handleMessage(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (SessionService::isOperator($cid)) {
            OperatorHandler::handleMessage($bot);
            return;
        }
        $bot->sendMessage("ℹ️ Siz admin sifatida kirdiniz.\n/start — Panelni ko'rish");
    }
}