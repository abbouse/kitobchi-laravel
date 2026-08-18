<?php

namespace App\Handlers;

use App\Services\SessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class AdminHandler
{
    public static function isAdmin(Nutgram $bot, int $chatId): bool
    {
        return SessionService::isAdmin($chatId);
    }

    public static function handleStart(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        Log::info("[Admin] /start panel ochildi", ['admin_id' => $cid]);

        $operators = SessionService::getOperators();
        $queue     = SessionService::getQueue();

        $onlineCount = 0;
        $busyCount   = 0;
        $breakCount  = 0;

        foreach ($operators as $opId) {
            $status = SessionService::getOperatorStatus($opId);
            if ($status === SessionService::OP_BUSY) $busyCount++;
            elseif ($status === SessionService::OP_BREAK) $breakCount++;
            elseif ($status === SessionService::OP_ONLINE) $onlineCount++;
        }

        $activeTicketsCount = DB::table('bot_tickets')->where('status', SessionService::STATUS_ACTIVE)->count();
        $totalTicketsCount  = DB::table('bot_tickets')->count();

        $text  = "👑 <b>Kitobchi Support — Bosh Administrator Paneli</b>\n\n";
        $text .= "👥 Jami operatorlar: <b>" . count($operators) . " ta</b>\n";
        $text .= "  • 🟢 Online: $onlineCount\n";
        $text .= "  • 🟡 Band (suhbatda): $busyCount\n";
        $text .= "  • ☕ Tanaffus: $breakCount\n\n";
        $text .= "🎫 Ticketlar holati:\n";
        $text .= "  • 📋 Navbatda: <b>" . count($queue) . " ta</b>\n";
        $text .= "  • 💬 Faol suhbatlar: <b>{$activeTicketsCount} ta</b>\n";
        $text .= "  • 📊 Jami murojaatlar: <b>{$totalTicketsCount} ta</b>\n\n";
        $text .= "<b>Buyruqlar:</b>\n" .
            "/operators — Operatorlar ro'yxati\n" .
            "/addop [ID] [Ism] — Operator qo'shish\n" .
            "/removeop [ID] — Operatorni o'chirish\n" .
            "/queue — Navbatni ko'rish\n" .
            "/close [ticket_id] — Bitta ticketni yopish\n" .
            "/closeall — Barcha ochiq ticketlarni yopish\n" .
            "/allstats — Umumiy statistika\n" .
            "/broadcast [matn] — Barcha mijozlarga e'lon\n";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make("👥 Operatorlar", callback_data: "admin_view_operators"),
                InlineKeyboardButton::make("📊 Statistika", callback_data: "admin_view_stats")
            )
            ->addRow(
                InlineKeyboardButton::make("📋 Navbat (" . count($queue) . ")", callback_data: "op_view_queue"),
                InlineKeyboardButton::make("🧹 Barchasini yopish", callback_data: "admin_close_all_prompt")
            );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleCloseAllPrompt(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        $count = DB::table('bot_tickets')->whereIn('status', [SessionService::STATUS_QUEUE, SessionService::STATUS_ACTIVE])->count();
        if ($count === 0) {
            $bot->sendMessage("📭 Hozirda ochiq yoki navbatda turgan ticketlar yo'q.");
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("⚠️ Ha, barchasini yopish ($count ta)", callback_data: "admin_confirm_close_all"),
            InlineKeyboardButton::make("❌ Bekor qilish", callback_data: "cancel_close")
        );

        $bot->sendMessage(
            "⚠️ <b>DIQQAT! Barcha ochiq ticketlarni yopish</b>\n\n" .
            "Hozirda tizimda <b>{$count} ta</b> ochiq va navbatdagi murojaatlar mavjud.\n" .
            "Barchasini birdaniga yopishni va operatorlarni bo'shatishni tasdiqlaysizmi?",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        );
    }

    public static function handleConfirmCloseAll(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) {
            $bot->answerCallbackQuery(text: "Ruxsat berilmagan!");
            return;
        }

        $closedCount = SessionService::closeAllOpenTickets('admin_bulk_closed');

        $bot->answerCallbackQuery(text: "✅ $closedCount ta ticket yopildi!");
        try {
            $bot->editMessageText("✅ <b>Barcha ochiq ticketlar ({$closedCount} ta) muvaffaqiyatli yopildi!</b>\nBarcha operatorlar qayta 🟢 ONLINE holatiga o'tkazildi.", parse_mode: 'HTML');
        } catch (\Throwable) {
            $bot->sendMessage("✅ <b>Barcha ochiq ticketlar ({$closedCount} ta) muvaffaqiyatli yopildi!</b>\nBarcha operatorlar qayta 🟢 ONLINE holatiga o'tkazildi.", parse_mode: 'HTML');
        }
    }

    public static function handleHelp(Nutgram $bot): void
    {
        self::handleStart($bot);
    }

    public static function handleListOperators(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        $operators = SessionService::getOperators();
        if (empty($operators)) {
            $bot->sendMessage("📭 Operatorlar ro'yxati bo'sh.\n/addop [TelegramID] [Ism] bilan qo'shing.");
            return;
        }

        $text = "👥 <b>Operatorlar ro'yxati (" . count($operators) . " ta):</b>\n\n";

        foreach ($operators as $opId) {
            $status = SessionService::getOperatorStatus($opId);
            $ticket = SessionService::getOperatorActiveTicket($opId);
            $stats  = SessionService::getStats($opId);

            $emoji = match ($status) {
                SessionService::OP_BUSY    => '🟡',
                SessionService::OP_BREAK   => '☕',
                SessionService::OP_OFFLINE => '🔴',
                default                    => '🟢',
            };

            $opInfo = DB::table('bot_operators')->where('telegram_id', $opId)->first();
            $opName = $opInfo?->name ? " ({$opInfo->name})" : "";

            $text .= "$emoji <code>$opId</code>{$opName}";
            if ($ticket) $text .= " — 🎫 #{$ticket->id}";
            $text .= "\n   ✅ Yopilgan: {$stats->closed} | ⭐ {$stats->avg_rating}/5\n\n";
        }

        $bot->sendMessage($text, parse_mode: 'HTML');
    }

    public static function handleAddOperator(Nutgram $bot, int $newId, ?string $name = null): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        if ($newId <= 0) {
            $bot->sendMessage("Foydalanish: /addop <code>[TelegramID]</code> <code>[Ism]</code>\nMisol: /addop 123456789 Jasur Aliyev", parse_mode: 'HTML');
            return;
        }

        if (SessionService::isOperator($newId)) {
            $bot->sendMessage("⚠️ Bu ID allaqachon operator: <code>$newId</code>", parse_mode: 'HTML');
            return;
        }

        SessionService::addOperator($newId, $name);
        $nameDisplay = $name ? " ($name)" : "";

        try {
            $bot->sendMessage(
                "🎉 <b>Assalomu alaykum! Siz Kitobchi Support operatori etib tayinlandingiz.</b>\n\n/start — Operator panelini ochish",
                chat_id: $newId,
                parse_mode: 'HTML'
            );
        } catch (\Throwable) {
            $bot->sendMessage("⚠️ <code>$newId</code> ga bildirishnoma yuborilmadi (bot bilan /start bosmagan bo'lishi mumkin).", parse_mode: 'HTML');
        }

        $bot->sendMessage("✅ Operator muvaffaqiyatli qo'shildi: <code>$newId</code>{$nameDisplay}", parse_mode: 'HTML');
    }

    public static function handleRemoveOperator(Nutgram $bot, int $rmId): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        if ($rmId <= 0) {
            $bot->sendMessage("Foydalanish: /removeop <code>[TelegramID]</code>", parse_mode: 'HTML');
            return;
        }

        $configOps = (array) config('nutgram.operators', []);
        if (in_array($rmId, $configOps)) {
            $bot->sendMessage("⚠️ Bu operator config faylida ko'rsatilgan.", parse_mode: 'HTML');
            return;
        }

        $activeTicket = SessionService::getOperatorActiveTicket($rmId);
        if ($activeTicket) {
            SessionService::closeTicket($activeTicket->id, 'operator_removed');
            try {
                $bot->sendMessage("Murojaatingiz texnik sabablarga ko'ra yopildi.", chat_id: (int) $activeTicket->user_id);
            } catch (\Throwable) {}
        }

        SessionService::removeOperator($rmId);

        try {
            $bot->sendMessage("Siz operatorlik ro'yxatidan chiqarildingiz.", chat_id: $rmId);
        } catch (\Throwable) {}

        $bot->sendMessage("✅ Operator o'chirildi: <code>$rmId</code>", parse_mode: 'HTML');
    }

    public static function handleAllStats(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        $operators    = SessionService::getOperators();
        $queue        = SessionService::getQueue();
        $totalTickets = DB::table('bot_tickets')->count();
        $totalRated   = DB::table('bot_tickets')->whereNotNull('rating')->count();
        $avgAllRating = DB::table('bot_tickets')->whereNotNull('rating')->avg('rating') ?: 0;

        $text  = "📊 <b>Kitobchi Support — Umumiy Tizim Statistikasi</b>\n\n";
        $text .= "🎫 Jami murojaatlar: <b>$totalTickets</b> ta\n";
        $text .= "📋 Navbatda kutayotgan: <b>" . count($queue) . "</b> ta\n";
        $text .= "🌟 O'rtacha tizim bahosi: <b>" . round($avgAllRating, 2) . " / 5</b> (" . $totalRated . " ta baholangan)\n\n";
        $text .= "<b>Operatorlar ko'rsatkichlari:</b>\n";

        $totalClosed = 0;
        foreach ($operators as $opId) {
            $stats       = SessionService::getStats($opId);
            $totalClosed += $stats->closed;
            $status      = SessionService::getOperatorStatus($opId);
            $opInfo      = DB::table('bot_operators')->where('telegram_id', $opId)->first();
            $opName      = $opInfo?->name ?: $opId;

            $emoji = match ($status) {
                SessionService::OP_BUSY    => '🟡',
                SessionService::OP_BREAK   => '☕',
                SessionService::OP_OFFLINE => '🔴',
                default                    => '🟢',
            };

            $text .= "$emoji <b>{$opName}</b>: {$stats->closed} ta yopilgan | ⭐ {$stats->avg_rating}\n";
        }

        $text .= "\n<b>Jami yakunlangan murojaatlar:</b> {$totalClosed} ta";
        $bot->sendMessage($text, parse_mode: 'HTML');
    }

    public static function handleBroadcast(Nutgram $bot, string $messageText): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        $msg = trim($messageText);
        if ($msg === '') {
            $bot->sendMessage("Foydalanish: /broadcast <code>[Xabar matni]</code>", parse_mode: 'HTML');
            return;
        }

        $userIds = DB::table('bot_tickets')
            ->distinct()
            ->pluck('user_id');

        $sent = 0;
        $failed = 0;

        $bot->sendMessage("📢 Broadcast yuborilmoqda (" . count($userIds) . " ta foydalanuvchiga)...");

        foreach ($userIds as $uid) {
            if (!$uid) continue;
            try {
                $bot->sendMessage("📢 <b>Kitobchi Support:</b>\n\n" . htmlspecialchars($msg), chat_id: (int) $uid, parse_mode: 'HTML');
                $sent++;
                usleep(50000); // 50ms delay to prevent Telegram rate limit
            } catch (\Throwable) {
                $failed++;
            }
        }

        $bot->sendMessage("✅ <b>Broadcast yakunlandi:</b>\n📤 Muvaffaqiyatli: $sent ta\n❌ Yetkazilmadi: $failed ta", parse_mode: 'HTML');
    }

    public static function handleSetupMenu(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isAdmin($bot, $cid)) return;

        try {
            // 1. Default (Foydalanuvchilar) menyusi
            $bot->setMyCommands([
                \SergiX44\Nutgram\Telegram\Types\Command\BotCommand::make('start', '🚀 Botni ishga tushirish'),
                \SergiX44\Nutgram\Telegram\Types\Command\BotCommand::make('help', 'ℹ️ Yordam va ma\'lumot'),
                \SergiX44\Nutgram\Telegram\Types\Command\BotCommand::make('cancel', '❌ Faol murojaatni bekor qilish'),
                \SergiX44\Nutgram\Telegram\Types\Command\BotCommand::make('myid', '🪪 Telegram ID ni ko\'rish'),
            ], scope: \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeDefault::make());

            $bot->sendMessage("✅ <b>Telegram Bot menyu buyruqlari muvaffaqiyatli sozlandi!</b>", parse_mode: 'HTML');
        } catch (\Throwable $e) {
            $bot->sendMessage("❌ Menyu sozlashda xatolik: " . htmlspecialchars($e->getMessage()));
        }
    }

    public static function handleMessage(Nutgram $bot): void
    {
        $cid = $bot->chatId();

        // Agar admin operator sifatida ticket bilan ishlayotgan bo'lsa yoki reply qilgan bo'lsa
        if (SessionService::isOperator($cid)) {
            OperatorHandler::handleMessage($bot);
            return;
        }

        $bot->sendMessage("👑 <b>Siz Administrator sifatida kirdingiz.</b>\n/start — Admin panelni ochish", parse_mode: 'HTML');
    }
}