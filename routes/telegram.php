<?php

use App\Handlers\UserHandler;
use App\Handlers\OperatorHandler;
use App\Handlers\AdminHandler;
use App\Services\SessionService;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;

/** @var Nutgram $bot */

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

// Operator buyruqlari
$bot->onCommand('end',    fn(Nutgram $bot) => OperatorHandler::handleEnd($bot));
$bot->onCommand('queue',  fn(Nutgram $bot) => OperatorHandler::handleQueue($bot));
$bot->onCommand('take',   fn(Nutgram $bot) => OperatorHandler::handleTake($bot));
$bot->onCommand('status', fn(Nutgram $bot) => OperatorHandler::handleStatus($bot));
$bot->onCommand('stats',  fn(Nutgram $bot) => OperatorHandler::handleStats($bot));

// /history — faol ticket yoki ID bilan
$bot->onCommand('history {ticketId}', function (Nutgram $bot, string $ticketId) {
    OperatorHandler::handleHistory($bot, $ticketId);
});
$bot->onCommand('history', function (Nutgram $bot) {
    OperatorHandler::handleHistory($bot);
});

// Admin buyruqlari
$bot->onCommand('addop {id} {name}', function (Nutgram $bot, string $id, string $name = '') {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;

    $newId = (int) $id;
    $name  = trim($name) ?: null;

    if (!$newId) {
        $bot->sendMessage(
            "Foydalanish: /addop <code>[ID]</code> <code>[Ism Familiya]</code>\n\nMisol: /addop 123456789 Abbos Turdaliev",
            parse_mode: 'HTML'
        );
        return;
    }

    if (SessionService::isOperator($newId)) {
        $bot->sendMessage("⚠️ Bu ID allaqachon operator: <code>$newId</code>", parse_mode: 'HTML');
        return;
    }

    SessionService::addOperator($newId, $name);
    $nameText = $name ? " ($name)" : "";

    try {
        $bot->sendMessage(
            "🎉 Siz support operator sifatida qo'shildingiz!\n\n/start — Panelni ochish",
            chat_id: $newId
        );
    } catch (\Throwable) {
        $bot->sendMessage("⚠️ <code>$newId</code> ga xabar yubora olmadim.", parse_mode: 'HTML');
    }

    $bot->sendMessage("✅ Operator qo'shildi: <code>$newId</code>{$nameText}", parse_mode: 'HTML');
    Log::info("[Admin] operator qo'shildi", ['new_id' => $newId, 'name' => $name]);
});

// Parametrsiz holat
$bot->onCommand('addop', fn(Nutgram $bot) => $bot->sendMessage(
    "Foydalanish: /addop <code>[ID]</code> <code>[Ism Familiya]</code>\n\nMisol: /addop 123456789 Abbos Turdaliev",
    parse_mode: 'HTML'
));
$bot->onCommand('addop {id}', function (Nutgram $bot, string $id) {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;

    $newId = (int) $id;

    // ID dan keyingi hamma narsani ism sifatida olish
    $fullText = $bot->message()?->text ?? '';
    // "/addop 123456789 Abbos Turdaliev" → "Abbos Turdaliev"
    $name = trim(preg_replace('/^\/addop\s+\d+\s*/i', '', $fullText)) ?: null;

    if (!$newId) {
        $bot->sendMessage(
            "Foydalanish: /addop <code>[ID]</code> <code>[Ism Familiya]</code>\n\nMisol: /addop 123456789 Abbos Turdaliev",
            parse_mode: 'HTML'
        );
        return;
    }

    if (SessionService::isOperator($newId)) {
        $bot->sendMessage("⚠️ Bu ID allaqachon operator: <code>$newId</code>", parse_mode: 'HTML');
        return;
    }

    SessionService::addOperator($newId, $name);
    $nameText = $name ? " ($name)" : "";

    try {
        $bot->sendMessage(
            "🎉 Siz support operator sifatida qo'shildingiz!\n\n/start — Panelni ochish",
            chat_id: $newId
        );
    } catch (\Throwable) {
        $bot->sendMessage("⚠️ <code>$newId</code> ga xabar yubora olmadim.", parse_mode: 'HTML');
    }

    $bot->sendMessage("✅ Operator qo'shildi: <code>$newId</code>{$nameText}", parse_mode: 'HTML');
});

$bot->onCommand('addop', fn(Nutgram $bot) => $bot->sendMessage(
    "Foydalanish: /addop <code>[ID]</code> <code>[Ism Familiya]</code>\n\nMisol: /addop 123456789 Abbos Turdaliev",
    parse_mode: 'HTML'
));

$bot->onCommand('removeop {id}', function (Nutgram $bot, string $id) {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;
    
    $rmId = (int) $id;
    if (!$rmId) {
        $bot->sendMessage("Foydalanish: /removeop <code>[ID]</code>", parse_mode: 'HTML');
        return;
    }
    
    // AdminHandler::handleRemoveOperator ga $rmId ni pass qilish uchun
    // To'g'ridan-to'g'ri logika yozing:
    $configOps = (array) config('nutgram.operators', []);
    if (in_array($rmId, $configOps)) {
        $bot->sendMessage("⚠️ Bu operator config faylida belgilangan.", parse_mode: 'HTML');
        return;
    }
    
    $activeTicket = SessionService::getOperatorActiveTicket($rmId);
    if ($activeTicket) {
        SessionService::closeTicket($activeTicket->id, 'operator_removed');
        try {
            $bot->sendMessage("Murojaatingiz texnik sabablarga ko'ra yopildi.", chat_id: $activeTicket->user_id);
        } catch (\Throwable) {}
    }
    
    SessionService::removeOperator($rmId);
    
    try {
        $bot->sendMessage("Siz operator ro'yxatidan o'chirilgansiz.", chat_id: $rmId);
    } catch (\Throwable) {}
    
    $bot->sendMessage("✅ Operator o'chirildi: <code>$rmId</code>", parse_mode: 'HTML');
});
$bot->onCommand('operators', fn(Nutgram $bot) => AdminHandler::handleListOperators($bot));
$bot->onCommand('broadcast {text}', function (Nutgram $bot, string $text) {
    $cid = $bot->chatId();
    if (!AdminHandler::isAdmin($bot, $cid)) return;
    AdminHandler::handleBroadcast($bot); // yoki to'g'ridan logika
});
$bot->onCommand('allstats',  fn(Nutgram $bot) => AdminHandler::handleAllStats($bot));

// Utility
$bot->onCommand('myid', fn(Nutgram $bot) =>
    $bot->sendMessage("🪪 Sizning ID: <code>{$bot->chatId()}</code>", parse_mode: 'HTML')
);

$bot->onCommand('cancel', function (Nutgram $bot) {
    $cid = $bot->chatId();
    if (!OperatorHandler::isOperator($bot, $cid) && !AdminHandler::isAdmin($bot, $cid)) {
        UserHandler::handleCancel($bot);
    }
});

// ── CALLBACK QUERIES ───────────────────────────────────────────────────────────

$bot->onCallbackQueryData('take_ticket:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleTakeCallback($bot));

$bot->onCallbackQueryData('confirm_close:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleConfirmClose($bot));

$bot->onCallbackQueryData('transfer_ticket:{ticketId}',
    fn(Nutgram $bot) => OperatorHandler::handleTransferCallback($bot));

$bot->onCallbackQueryData('cancel_close',
    fn(Nutgram $bot) => $bot->answerCallbackQuery(text: "❌ Bekor qilindi"));

$bot->onCallbackQueryData('op_status:{status}',
    fn(Nutgram $bot) => OperatorHandler::handleOpStatusCallback($bot));

$bot->onCallbackQueryData('rate:{ticketId}:{score}',
    fn(Nutgram $bot) => UserHandler::handleRatingCallback($bot));

// ── ODDIY XABARLAR ─────────────────────────────────────────────────────────────

$bot->onMessage(function (Nutgram $bot) {
    $message = $bot->message();
    if (!$message) return;

    $text = $message->text ?? '';
    if (str_starts_with($text, '/')) return;

    $cid = $bot->chatId();

    if (AdminHandler::isAdmin($bot, $cid))       { AdminHandler::handleMessage($bot);    return; }
    if (OperatorHandler::isOperator($bot, $cid)) { OperatorHandler::handleMessage($bot); return; }
    UserHandler::handleMessage($bot);
});

// ── EXCEPTION HANDLER ──────────────────────────────────────────────────────────

$bot->onException(function (Nutgram $bot, \Throwable $e) {
    Log::error("Nutgram global xatosi", [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'chat_id' => $bot->chatId() ?? 'unknown',
        'trace'   => $e->getTraceAsString(),
    ]);
    try {
        $bot->sendMessage("⚠️ Ichki xatolik yuz berdi. Administratorga xabar berildi.");
    } catch (\Throwable) {}
});