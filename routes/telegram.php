<?php

use App\Handlers\UserHandler;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;

/** @var Nutgram $bot */

/*
| Kitobchi support bot — faqat MIJOZLAR uchun.
| Operatorlar endi Telegram orqali javob bermaydi: barcha murojaatlar boshqaruvdagi
| "Support inbox" bo'limiga real-vaqtda tushadi va javob o'sha yerdan yuboriladi.
*/

// ─── BUYRUQLAR ────────────────────────────────────────────────────────────────

$bot->onCommand('start', fn (Nutgram $bot) => UserHandler::handleStart($bot));
$bot->onCommand('help', fn (Nutgram $bot) => UserHandler::handleHelp($bot));
$bot->onCommand('cancel', fn (Nutgram $bot) => UserHandler::handleCancel($bot));
$bot->onCommand('myid', fn (Nutgram $bot) =>
    $bot->sendMessage("🪪 Sizning Telegram ID: <code>{$bot->chatId()}</code>", parse_mode: 'HTML')
);

// ─── CALLBACK QUERIES ─────────────────────────────────────────────────────────

$bot->onCallbackQueryData('user_faq_menu', fn (Nutgram $bot) => UserHandler::handleStart($bot));
$bot->onCallbackQueryData('user_faq_order', fn (Nutgram $bot) => UserHandler::handleFaqOrder($bot));
$bot->onCallbackQueryData('user_faq_delivery', fn (Nutgram $bot) => UserHandler::handleFaqDelivery($bot));
$bot->onCallbackQueryData('user_faq_payment', fn (Nutgram $bot) => UserHandler::handleFaqPayment($bot));
$bot->onCallbackQueryData('user_faq_cashback', fn (Nutgram $bot) => UserHandler::handleFaqCashback($bot));
$bot->onCallbackQueryData('user_faq_app', fn (Nutgram $bot) => UserHandler::handleFaqApp($bot));
$bot->onCallbackQueryData('user_connect_operator', function (Nutgram $bot) {
    $bot->answerCallbackQuery();
    $bot->sendMessage("💬 <b>Operatorga xabar yo'llash:</b>\n\nSavolingizni yozing (matn, rasm, ovozli xabar yoki hujjat) — operatorimiz tez orada shu yerda javob beradi!", parse_mode: 'HTML');
});

// Baho: yangi (yaxshi/yomon) va eski (1–5 yulduz) xabarlar uchun
$bot->onCallbackQueryData('fb:{ticketId}:{value}', fn (Nutgram $bot) => UserHandler::handleFeedbackCallback($bot));
$bot->onCallbackQueryData('rate:{ticketId}:{score}', fn (Nutgram $bot) => UserHandler::handleRatingCallback($bot));

// ─── ODDIY XABARLAR ───────────────────────────────────────────────────────────

$bot->onMessage(function (Nutgram $bot) {
    $message = $bot->message();
    if (!$message) return;

    $text = trim($message->text ?? '');
    if (str_starts_with($text, '/')) {
        // Eski operator buyruqlari (/take, /queue, /end ...) — endi boshqaruv panelida
        $command = strtolower(ltrim(strtok($text, " @") ?: '', '/'));
        if (in_array($command, ['queue', 'take', 'current', 'end', 'close', 'note', 'status', 'stats', 'quick', 'shablon', 'history', 'closeall', 'addop', 'removeop', 'operators', 'broadcast', 'allstats', 'setupmenu'], true)) {
            $bot->sendMessage("ℹ️ Operatorlar endi murojaatlarga Kitobchi boshqaruv panelidagi «Support inbox» bo'limidan javob beradi.");
        }
        return;
    }

    UserHandler::handleMessage($bot);
});

// ─── EXCEPTION HANDLER ────────────────────────────────────────────────────────

$bot->onException(function (Nutgram $bot, \Throwable $e) {
    $msg = $e->getMessage();

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
