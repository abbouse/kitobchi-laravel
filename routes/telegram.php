<?php

use App\Handlers\UserHandler;
use App\Handlers\OperatorHandler;
use App\Handlers\AdminHandler;
use App\Services\SessionService;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;

/** @var Nutgram $bot */

// ─── BUYRUQLAR (COMMANDS) ──────────────────────────────────────────────────────

$bot->onCommand('start', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (AdminHandler::isAdmin($bot, $cid))       { AdminHandler::handleStart($bot);    return; }
    if (OperatorHandler::isOperator($bot, $cid)) { OperatorHandler::handleStart($bot); return; }
    UserHandler::handleStart($bot);
});

$bot->onCommand('help', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (AdminHandler::isAdmin($bot, $cid))       { AdminHandler::handleHelp($bot);    return; }
    if (OperatorHandler::isOperator($bot, $cid)) { OperatorHandler::handleHelp($bot); return; }
    UserHandler::handleHelp($bot);
});

// ─── OPERATOR VA TICKET BUYRUQLARI ─────────────────────────────────────────────

$bot->onCommand('queue',   fn(Nutgram $bot) => OperatorHandler::handleQueue($bot));
$bot->onCommand('take {ticketId}', function (Nutgram $bot, string $ticketId) {
    OperatorHandler::handleTake($bot, (int) $ticketId);
});
$bot->onCommand('take',    fn(Nutgram $bot) => OperatorHandler::handleTake($bot));
$bot->onCommand('current', fn(Nutgram $bot) => OperatorHandler::handleCurrent($bot));

$bot->onCommand('end {ticketId}', function (Nutgram $bot, string $ticketId) {
    OperatorHandler::handleEnd($bot, (int) $ticketId);
});
$bot->onCommand('end',     fn(Nutgram $bot) => OperatorHandler::handleEnd($bot));

$bot->onCommand('close {ticketId}', function (Nutgram $bot, string $ticketId) {
    OperatorHandler::handleEnd($bot, (int) $ticketId);
});
$bot->onCommand('close',   fn(Nutgram $bot) => OperatorHandler::handleEnd($bot));

$bot->onCommand('note', function (Nutgram $bot) {
    $fullText = $bot->message()?->text ?? '';
    $noteText = trim(preg_replace('/^\/note\s*/i', '', $fullText));
    OperatorHandler::handleNote($bot, $noteText);
});

$bot->onCommand('status',  fn(Nutgram $bot) => OperatorHandler::handleStatus($bot));
$bot->onCommand('stats',   fn(Nutgram $bot) => OperatorHandler::handleStats($bot));
$bot->onCommand('quick',   fn(Nutgram $bot) => OperatorHandler::handleQuickReplies($bot));
$bot->onCommand('shablon', fn(Nutgram $bot) => OperatorHandler::handleQuickReplies($bot));

$bot->onCommand('history {ticketId}', function (Nutgram $bot, string $ticketId) {
    OperatorHandler::handleHistory($bot, $ticketId);
});
$bot->onCommand('history', function (Nutgram $bot) {
    OperatorHandler::handleHistory($bot);
});

// ─── ADMIN BUYRUQLARI ─────────────────────────────────────────────────────────

$bot->onCommand('closeall', fn(Nutgram $bot) => AdminHandler::handleCloseAllPrompt($bot));
$bot->onCommand('setupmenu', fn(Nutgram $bot) => AdminHandler::handleSetupMenu($bot));

$bot->onCommand('addop', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;

    $fullText = $bot->message()?->text ?? '';
    // Format: /addop 123456789 Jasur Aliyev
    if (preg_match('/^\/addop\s+(\d+)(?:\s+(.*))?$/i', trim($fullText), $matches)) {
        $newId = (int) $matches[1];
        $name  = !empty($matches[2]) ? trim($matches[2]) : null;
        AdminHandler::handleAddOperator($bot, $newId, $name);
    } else {
        $bot->sendMessage(
            "Foydalanish: /addop <code>[TelegramID]</code> <code>[Ism]</code>\n\nMisol: /addop 123456789 Jasur Aliyev",
            parse_mode: 'HTML'
        );
    }
});

$bot->onCommand('removeop {id}', function (Nutgram $bot, string $id) {
    AdminHandler::handleRemoveOperator($bot, (int) $id);
});

$bot->onCommand('operators', fn(Nutgram $bot) => AdminHandler::handleListOperators($bot));

$bot->onCommand('broadcast', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;

    $fullText = $bot->message()?->text ?? '';
    $msgText  = trim(preg_replace('/^\/broadcast\s*/i', '', $fullText));
    AdminHandler::handleBroadcast($bot, $msgText);
});

$bot->onCommand('allstats', fn(Nutgram $bot) => AdminHandler::handleAllStats($bot));

// ─── UMUMIY BUYRUQLAR ─────────────────────────────────────────────────────────

$bot->onCommand('myid', fn(Nutgram $bot) =>
    $bot->sendMessage("🪪 Sizning Telegram ID: <code>{$bot->chatId()}</code>", parse_mode: 'HTML')
);

$bot->onCommand('cancel', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (!OperatorHandler::isOperator($bot, $cid) && !AdminHandler::isAdmin($bot, $cid)) {
        UserHandler::handleCancel($bot);
    }
});

// ─── CALLBACK QUERIES ─────────────────────────────────────────────────────────

// Foydalanuvchi FAQ menyulari
$bot->onCallbackQueryData('user_faq_menu', fn(Nutgram $bot) => UserHandler::handleStart($bot));
$bot->onCallbackQueryData('user_faq_order', fn(Nutgram $bot) => UserHandler::handleFaqOrder($bot));
$bot->onCallbackQueryData('user_faq_delivery', fn(Nutgram $bot) => UserHandler::handleFaqDelivery($bot));
$bot->onCallbackQueryData('user_faq_payment', fn(Nutgram $bot) => UserHandler::handleFaqPayment($bot));
$bot->onCallbackQueryData('user_faq_cashback', fn(Nutgram $bot) => UserHandler::handleFaqCashback($bot));
$bot->onCallbackQueryData('user_faq_app', fn(Nutgram $bot) => UserHandler::handleFaqApp($bot));
$bot->onCallbackQueryData('user_connect_operator', function (Nutgram $bot) {
    $bot->answerCallbackQuery();
    $bot->sendMessage("💬 <b>Operatorga xabar yo'llash:</b>\n\nSavolingiz yoki murojaatingizni yozing (matn, rasm, ovozli xabar yoki hujjat) — navbatdagi bo'sh operatorimiz tez orada javob beradi!", parse_mode: 'HTML');
});

// Operator va Admin callbacks
$bot->onCallbackQueryData('take_ticket:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleTakeCallback($bot));

$bot->onCallbackQueryData('confirm_close:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleConfirmClose($bot));

$bot->onCallbackQueryData('transfer_ticket:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleTransferCallback($bot));

$bot->onCallbackQueryData('cancel_close', function (Nutgram $bot) {
    $bot->answerCallbackQuery(text: "❌ Bekor qilindi");
    try {
        $bot->editMessageText("❌ Amal bekor qilindi.");
    } catch (\Throwable) {}
});

$bot->onCallbackQueryData('admin_close_all_prompt',
    fn(Nutgram $bot) => AdminHandler::handleCloseAllPrompt($bot));

$bot->onCallbackQueryData('admin_close_queue_prompt',
    fn(Nutgram $bot) => AdminHandler::handleCloseAllPrompt($bot));

$bot->onCallbackQueryData('admin_confirm_close_all',
    fn(Nutgram $bot) => AdminHandler::handleConfirmCloseAll($bot));

$bot->onCallbackQueryData('op_status:{status}',
    fn(Nutgram $bot) => OperatorHandler::handleOpStatusCallback($bot));

$bot->onCallbackQueryData('op_canned_list',
    fn(Nutgram $bot) => OperatorHandler::handleQuickReplies($bot));

$bot->onCallbackQueryData('op_canned_cat:{category}', function (Nutgram $bot) {
    $data = $bot->callbackQuery()->data;
    $cat  = explode(':', $data)[1] ?? null;
    OperatorHandler::handleQuickReplies($bot, $cat);
});

$bot->onCallbackQueryData('op_current_ticket',
    fn(Nutgram $bot) => OperatorHandler::handleCurrent($bot));

$bot->onCallbackQueryData('op_canned_send:{key}', function (Nutgram $bot) {
    $data = $bot->callbackQuery()->data;
    $key  = explode(':', $data)[1] ?? '';
    OperatorHandler::handleSendQuickReply($bot, $key);
});

// ─── INLINE QUERY QIDIRUV (TELEGRAM BOT API) ──────────────────────────────────

$bot->onInlineQuery(fn(Nutgram $bot) => OperatorHandler::handleInlineQuery($bot));

$bot->onCallbackQueryData('op_view_queue',
    fn(Nutgram $bot) => OperatorHandler::handleQueue($bot));

$bot->onCallbackQueryData('op_history:{ticketId}', function (Nutgram $bot) {
    $data     = $bot->callbackQuery()->data;
    $ticketId = explode(':', $data)[1] ?? null;
    OperatorHandler::handleHistory($bot, $ticketId);
});

$bot->onCallbackQueryData('admin_view_operators',
    fn(Nutgram $bot) => AdminHandler::handleListOperators($bot));

$bot->onCallbackQueryData('admin_view_stats',
    fn(Nutgram $bot) => AdminHandler::handleAllStats($bot));

$bot->onCallbackQueryData('rate:{ticketId}:{score}',
    fn(Nutgram $bot) => UserHandler::handleRatingCallback($bot));

// ─── ODDIY XABARLAR (MESSAGES) ─────────────────────────────────────────────────

$bot->onMessage(function (Nutgram $bot) {
    $message = $bot->message();
    if (!$message) return;

    $text = trim($message->text ?? '');

    // Buyruqlarni o'tkazib yuborish
    if (str_starts_with($text, '/')) return;

    $cid = $bot->chatId();
    $isOperator = OperatorHandler::isOperator($bot, $cid);
    $isAdmin    = AdminHandler::isAdmin($bot, $cid);

    // Operator klaviatura tugmalari bosilganda
    if ($isOperator || $isAdmin) {
        match ($text) {
            '📋 Navbat'          => OperatorHandler::handleQueue($bot),
            '🎫 Faol murojaat'   => OperatorHandler::handleCurrent($bot),
            '⚡ Tezkor javoblar' => OperatorHandler::handleQuickReplies($bot),
            '📊 Statistika'      => OperatorHandler::handleStats($bot),
            '⚙️ Status'          => OperatorHandler::handleStatus($bot),
            default              => null,
        };

        if (in_array($text, ['📋 Navbat', '🎫 Faol murojaat', '⚡ Tezkor javoblar', '📊 Statistika', '⚙️ Status'])) {
            return;
        }
    }

    if ($isAdmin) {
        AdminHandler::handleMessage($bot);
        return;
    }

    if ($isOperator) {
        OperatorHandler::handleMessage($bot);
        return;
    }

    UserHandler::handleMessage($bot);
});

// ─── EXCEPTION HANDLER ────────────────────────────────────────────────────────

$bot->onException(function (Nutgram $bot, \Throwable $e) {
    $msg = $e->getMessage();

    // Telegram API normal ogohlantirishlarini (masalan: message not modified, query too old, bot blocked) e'tiborsiz qoldiramiz
    if (
        str_contains($msg, 'message is not modified') ||
        str_contains($msg, 'query is too old') ||
        str_contains($msg, 'bot was blocked by the user') ||
        str_contains($msg, 'user is deactivated') ||
        str_contains($msg, 'chat not found')
    ) {
        Log::info("Nutgram oddiy bildirishnoma: " . $msg);
        return;
    }

    Log::error("Nutgram global xatosi", [
        'message' => $msg,
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'chat_id' => $bot->chatId() ?? 'unknown',
    ]);

    if ($bot->callbackQuery()) {
        try {
            $bot->answerCallbackQuery(text: "⚠️ Xatolik yuz berdi!", show_alert: false);
        } catch (\Throwable) {}
        return;
    }

    if ($bot->chatId()) {
        try {
            $bot->sendMessage("⚠️ Kutilmagan xatolik yuz berdi. Iltimos, /start buyrug'ini yuboring.");
        } catch (\Throwable) {}
    }
});