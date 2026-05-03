<?php

namespace App\Handlers;

use App\Services\SessionService;
use App\Services\SupportChatBridgeService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class OperatorHandler
{
    public static function isOperator(Nutgram $bot, int $chatId): bool
    {
        $isOp = SessionService::isOperator($chatId);
        Log::debug("[Operator] isOperator tekshiruvi", ['chat_id' => $chatId, 'natija' => $isOp]);
        return $isOp;
    }

    public static function handleStart(Nutgram $bot): void
    {
        $cid    = $bot->chatId();
        $status = SessionService::getOperatorStatus($cid);
        $ticket = SessionService::getOperatorActiveTicket($cid);
        $queue  = SessionService::getQueue();

        $emoji = match($status) {
            SessionService::OP_BUSY    => '🟡',
            SessionService::OP_OFFLINE => '🔴',
            default                    => '🟢',
        };

        $text  = "👋 <b>Operator paneli</b>\n\n";
        $text .= "$emoji Status: <b>$status</b>\n";
        $text .= "🆔 ID: <code>$cid</code>\n";
        $text .= "📋 Navbat: " . count($queue) . " ta murojaat\n";

        if ($ticket) {
            $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
            $text .= "\n🎫 Faol ticket: #{$ticket->id}\n👤 $userDisplay\n";
        }

        $text .= "\n<b>Buyruqlar:</b>\n/queue — Navbat\n/take — Qabul qilish\n/end — Yopish\n/status — Status\n/stats — Statistika";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("🟢 Online",  callback_data: "op_status:online"),
            InlineKeyboardButton::make("🔴 Offline", callback_data: "op_status:offline"),
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
        Log::info("[Operator] panel yuborildi", ['operator_id' => $cid]);
    }

    public static function handleHelp(Nutgram $bot): void
    {
        $bot->sendMessage(
            "📖 <b>Operator qo'llanmasi</b>\n\n" .
            "/start — Panel\n/queue — Navbat\n/take — Birinchi murojaatni qabul qilish\n" .
            "/end — Suhbatni yopish\n/status — Online/Offline\n/stats — Statistika\n" .
            "/history [ticket_id] — Ticket ilovalar tarixi\n\n" .
            "<b>Ishlash tartibi:</b>\n" .
            "1️⃣ Yangi murojaat kelganda bildirishnoma olasiz\n" .
            "2️⃣ «✋ Qabul qilish» tugmasini bosing\n" .
            "3️⃣ Foydalanuvchi bilan suhbatlashing\n" .
            "4️⃣ /end bilan yoping\n" .
            "5️⃣ Mijoz baho beradi ⭐",
            parse_mode: 'HTML'
        );
    }

    public static function handleQueue(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $queue = SessionService::getQueue();
        if (empty($queue)) {
            $bot->sendMessage("📭 Navbat bo'sh!");
            return;
        }

        $text = "📋 <b>Navbat:</b> " . count($queue) . " ta\n\n";
        foreach (array_slice($queue, 0, 10) as $i => $ticket) {
            $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
            $preview     = mb_substr($ticket->first_msg ?? '', 0, 60);
            $text .= ($i + 1) . ". 🎫 #{$ticket->id} — $userDisplay\n";
            $text .= "   📝 <i>" . htmlspecialchars($preview) . "</i>\n\n";
        }

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("✋ Birinchisini qabul qilish", callback_data: "take_ticket:{$queue[0]->id}")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleTake(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $active = SessionService::getOperatorActiveTicket($cid);
        if ($active) {
            $bot->sendMessage("⚠️ Sizda faol murojaat bor: Ticket #{$active->id}\nAvval /end bilan yoping.");
            return;
        }

        $queue = SessionService::getQueue();
        if (empty($queue)) {
            $bot->sendMessage("📭 Navbat bo'sh.");
            return;
        }

        self::assignAndNotify($bot, $queue[0]->id, $cid);
    }

    public static function handleEnd(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticket = SessionService::getOperatorActiveTicket($cid);
        if (!$ticket) {
            $bot->sendMessage("Sizda faol murojaat yo'q.");
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("✅ Ha, yopish", callback_data: "confirm_close:{$ticket->id}"),
            InlineKeyboardButton::make("❌ Bekor",      callback_data: "cancel_close"),
        );

        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
        $bot->sendMessage(
            "🔴 Suhbatni yopmoqchimisiz?\n🎫 Ticket #{$ticket->id}\n👤 $userDisplay",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        );
    }

    public static function handleStatus(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $current  = SessionService::getOperatorStatus($cid);
        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("🟢 Online",  callback_data: "op_status:online"),
            InlineKeyboardButton::make("🔴 Offline", callback_data: "op_status:offline"),
        );
        $bot->sendMessage("Joriy status: <b>$current</b>\n\nYangi status tanlang:", parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleOpStatusCallback(Nutgram $bot): void
    {
        $data   = $bot->callbackQuery()->data;
        $status = explode(':', $data)[1];
        $cid    = $bot->chatId();

        if (!self::isOperator($bot, $cid)) {
            $bot->answerCallbackQuery(text: "Ruxsat yo'q!");
            return;
        }

        SessionService::setOperatorStatus($cid, $status);
        $emoji = $status === SessionService::OP_ONLINE ? '🟢' : '🔴';
        $bot->answerCallbackQuery(text: "$emoji Status: $status");
        $bot->sendMessage("$emoji Statusingiz: <b>$status</b>", parse_mode: 'HTML');
        Log::info("[Operator] status o'zgardi", ['op_id' => $cid, 'status' => $status]);
    }

    public static function handleStats(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $stats = SessionService::getStats($cid);
        $stars = $stats->avg_rating > 0
            ? str_repeat('⭐', (int) round($stats->avg_rating)) . " ({$stats->avg_rating}/5)"
            : "Baholanmagan";

        $bot->sendMessage(
            "📊 <b>Statistika</b>\n\n✅ Yopilgan: {$stats->closed}\n🌟 Baho: $stars\n📝 Baholashlar: {$stats->total_rated}",
            parse_mode: 'HTML'
        );
    }

    /**
     * /history [ticket_id] — Ticket ilovalar tarixini ko'rish.
     * ticket_id berilmasa, faol ticket ishlatiladi.
     */
    public static function handleHistory(Nutgram $bot, ?string $ticketIdParam = null): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        // Ticket ID aniqlash: parametrdan yoki faol ticketdan
        $ticketId = $ticketIdParam ? (int) $ticketIdParam : null;

        if (!$ticketId) {
            $active = SessionService::getOperatorActiveTicket($cid);
            if (!$active) {
                $bot->sendMessage("Foydalanish: /history <code>[ticket_id]</code>\nYoki faol ticket bo'lsa shunchaki /history", parse_mode: 'HTML');
                return;
            }
            $ticketId = $active->id;
        }

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket) {
            $bot->sendMessage("❌ Ticket #$ticketId topilmadi.");
            return;
        }

        $attachments = SessionService::getTicketAttachments($ticketId);

        if (empty($attachments)) {
            $bot->sendMessage("📭 Ticket #$ticketId da ilovalar yo'q.");
            return;
        }

        // Ilovalarni guruhlab ko'rsatish
        $userCount  = 0;
        $opCount    = 0;
        $typeCount  = [];

        foreach ($attachments as $att) {
            if ($att->sent_by === 'user') $userCount++;
            else $opCount++;
            $typeCount[$att->file_type] = ($typeCount[$att->file_type] ?? 0) + 1;
        }

        $typeSummary = [];
        $typeEmojis  = ['photo' => '🖼', 'document' => '📄', 'voice' => '🎤', 'video' => '🎬'];
        foreach ($typeCount as $type => $count) {
            $emoji         = $typeEmojis[$type] ?? '📎';
            $typeSummary[] = "$emoji $type: $count";
        }

        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
        $text  = "📎 <b>Ticket #{$ticketId} ilovalar tarixi</b>\n";
        $text .= "👤 $userDisplay\n";
        $text .= "📊 Jami: " . count($attachments) . " ta\n";
        $text .= "  👤 User: $userCount | 🧑‍💻 Operator: $opCount\n";
        $text .= "  " . implode(' · ', $typeSummary) . "\n\n";
        $text .= "Fayllar quyida yuboriladi 👇";

        $bot->sendMessage($text, parse_mode: 'HTML');

        // Har bir ilovani forward qilish
        foreach ($attachments as $att) {
            $sentByLabel = $att->sent_by === 'user' ? '👤 User' : '🧑‍💻 Operator';
            $time        = \Carbon\Carbon::parse($att->created_at)->format('d.m H:i');
            $caption     = "$sentByLabel · $time";

            if ($att->file_name) {
                $caption .= "\n📄 {$att->file_name}";
            }

            try {
                match($att->file_type) {
                    'photo'    => $bot->sendPhoto(photo: $att->file_id, chat_id: $cid, caption: $caption),
                    'document' => $bot->sendDocument(document: $att->file_id, chat_id: $cid, caption: $caption),
                    'voice'    => $bot->sendVoice(voice: $att->file_id, chat_id: $cid, caption: $caption),
                    'video'    => $bot->sendVideo(video: $att->file_id, chat_id: $cid, caption: $caption),
                    default    => $bot->sendMessage("📎 Noma'lum fayl turi: {$att->file_type}", chat_id: $cid),
                };
            } catch (\Throwable $e) {
                Log::warning("[Operator] history fayl yuborishda xato", ['file_id' => $att->file_id, 'error' => $e->getMessage()]);
                $bot->sendMessage("⚠️ Fayl yuborishda xato: {$att->file_type}", chat_id: $cid);
            }
        }

        Log::info("[Operator] history yuborildi", ['ticket_id' => $ticketId, 'count' => count($attachments)]);
    }

    public static function handleTakeCallback(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) explode(':', $data)[1];
        $cid      = $bot->chatId();

        if (!self::isOperator($bot, $cid)) {
            $bot->answerCallbackQuery(text: "Sizda ruxsat yo'q!");
            return;
        }

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket) {
            $bot->answerCallbackQuery(text: "Ticket topilmadi");
            return;
        }

        if ($ticket->status !== SessionService::STATUS_QUEUE) {
            $bot->answerCallbackQuery(text: "Bu ticket allaqachon qabul qilingan!");
            return;
        }

        $active = SessionService::getOperatorActiveTicket($cid);
        if ($active) {
            $bot->answerCallbackQuery(text: "Avval Ticket #{$active->id} ni yoping!", show_alert: true);
            return;
        }

        self::assignAndNotify($bot, $ticketId, $cid);
        $bot->answerCallbackQuery(text: "✅ Ticket qabul qilindi!");
        self::notifyOtherOperators($bot, $ticket, $cid);
        Log::info("[Operator] ticket qabul qilindi", ['ticket_id' => $ticketId]);
    }

    public static function handleConfirmClose(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) explode(':', $data)[1];
        $cid      = $bot->chatId();

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || (int)$ticket->operator_id !== $cid) {
            $bot->answerCallbackQuery(text: "Ruxsat yo'q");
            return;
        }

        self::closeAndNotify($bot, $ticketId);
        $bot->answerCallbackQuery(text: "✅ Yopildi");
        Log::info("[Operator] ticket yopildi", ['ticket_id' => $ticketId]);
    }

    public static function handleTransferCallback(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) explode(':', $data)[1];
        $cid      = $bot->chatId();

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || (int)$ticket->operator_id !== $cid) {
            $bot->answerCallbackQuery(text: "Ruxsat yo'q");
            return;
        }

        SessionService::updateTicket($ticketId, ['status' => SessionService::STATUS_QUEUE, 'operator_id' => null]);
        SessionService::setOperatorStatus($cid, SessionService::OP_ONLINE);
        SessionService::saveSystemMessage($ticketId, "Operator ticketni navbatga qaytardi.");
        $bot->answerCallbackQuery(text: "Murojaat navbatga qaytarildi");
        $bot->sendMessage("🔄 Ticket #$ticketId navbatga qaytarildi.", chat_id: $cid);
        UserHandler::dispatchTicket($bot, (array) $ticket);
        Log::info("[Operator] ticket navbatga qaytarildi", ['ticket_id' => $ticketId]);
    }

    public static function handleMessage(Nutgram $bot): void
    {
        $cid     = $bot->chatId();
        $message = $bot->message();
        if (!$message) return;

        Log::info("[Operator] xabar keldi", ['operator_id' => $cid, 'type' => $message->getType()]);

        $ticket = SessionService::getOperatorActiveTicket($cid);
        if (!$ticket) {
            $queue    = SessionService::getQueue();
            $keyboard = null;
            if (!empty($queue)) {
                $keyboard = InlineKeyboardMarkup::make()->addRow(
                    InlineKeyboardButton::make("📋 Navbatni ko'rish", callback_data: "take_ticket:{$queue[0]->id}")
                );
            }
            $bot->sendMessage(
                empty($queue) ? "Navbat bo'sh — dam oling 🙂" : "📋 /queue — Navbatni ko'ring.",
                reply_markup: $keyboard
            );
            return;
        }

        if ($ticket->source_type === 'shop_chat' && $ticket->source_conversation_id) {
            $body = $message->text ?? $message->caption ?? '[media]';
            self::saveAttachment($message, $ticket->id, 'operator');
            app(SupportChatBridgeService::class)->sendReplyToConversation($ticket, $body, $cid);
            return;
        }

        $userId = (int) $ticket->user_id;

        self::saveMessage($message, (int) $ticket->id, 'operator', $cid);
        self::saveAttachment($message, $ticket->id, 'operator');

        try {
            $bot->copyMessage(chat_id: $userId, from_chat_id: $cid, message_id: $message->message_id);
            Log::info("[Operator] xabar userga yuborildi", ['ticket_id' => $ticket->id]);
        } catch (\Throwable $e) {
            Log::error("[Operator] nusxalash xatosi", ['error' => $e->getMessage()]);
            $bot->sendMessage("⚠️ Xabar foydalanuvchiga yetkazilmadi. Bot bloklangan bo'lishi mumkin.");
        }
    }

    // ─── Ichki yordamchilar ───────────────────────────────────────────────────

    public static function assignAndNotify(Nutgram $bot, int $ticketId, int $opId): void
    {
        Log::info("[Operator] assignAndNotify boshlandi", ['ticket_id' => $ticketId, 'op_id' => $opId]);

        SessionService::assignOperator($ticketId, $opId);
        $ticket = SessionService::getTicket($ticketId);
        SessionService::saveSystemMessage($ticketId, "Operator ticketni qabul qildi.");

        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);

        // Oldingi murojaatlar tarixi
        $prevTickets = \Illuminate\Support\Facades\DB::table('bot_tickets')
            ->where('user_id', $ticket->user_id)
            ->whereIn('status', [SessionService::STATUS_CLOSED, SessionService::STATUS_RATED])
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("🔄 Transfer", callback_data: "transfer_ticket:$ticketId"),
            InlineKeyboardButton::make("🔴 Yopish",   callback_data: "confirm_close:$ticketId"),
        );

        $text  = "✅ <b>Ticket #$ticketId qabul qilindi!</b>\n";
        $text .= "👤 $userDisplay\n";
        $text .= "🆔 {$ticket->user_id}\n\n";
        $text .= "📝 <i>" . htmlspecialchars($ticket->first_msg ?? '') . "</i>\n";

        if ($prevTickets->count() > 0) {
            $text .= "\n📜 <b>Oldingi murojaatlar:</b>\n";
            foreach ($prevTickets as $pt) {
                $text .= "  • #{$pt->id} — " . htmlspecialchars(mb_substr($pt->first_msg ?? '', 0, 50)) . "\n";
            }
        }

        $text .= "\n/history {$ticketId} — Ilovalarni ko'rish\n/end — Yopish";

        $bot->sendMessage($text, chat_id: $opId, parse_mode: 'HTML', reply_markup: $keyboard);
        if ($ticket->source_type !== 'shop_chat') {
            $bot->sendMessage("🟢 Operator siz bilan bog'landi! Xabar yuboring.", chat_id: $ticket->user_id);
        }

        Log::info("[Operator] ticket qabul qilindi va bildirildi");
    }

    public static function closeAndNotify(Nutgram $bot, int $ticketId): void
    {
        Log::info("[Operator] closeAndNotify boshlandi", ['ticket_id' => $ticketId]);

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket) return;

        // Ilovalar soni
        $attCount = count(SessionService::getTicketAttachments($ticketId));
        $attText  = $attCount > 0 ? "\n📎 $attCount ta ilova saqlangan (/history $ticketId)" : "";

        SessionService::closeTicket($ticketId, 'operator_closed');
        SessionService::saveSystemMessage($ticketId, "Ticket operator tomonidan yopildi.");
        $opId = (int) $ticket->operator_id;

        $bot->sendMessage("✅ Ticket #$ticketId yopildi.{$attText}", chat_id: $opId, parse_mode: 'HTML');
        if ($ticket->source_type !== 'shop_chat') {
            UserHandler::sendRatingRequest($bot, (int) $ticket->user_id, $ticketId);
        }

        Log::info("[Operator] ticket yopildi va baholash so'raldi");
    }

    /**
     * Xabardan fayl ma'lumotini olib bot_ticket_attachments ga saqlaydi.
     */
    private static function saveAttachment(
        \SergiX44\Nutgram\Telegram\Types\Message\Message $message,
        int $ticketId,
        string $sentBy
    ): void {
        $fileId   = null;
        $fileType = null;
        $fileName = null;
        $fileSize = null;

        if ($message->photo) {
            $photo    = end($message->photo); // eng yuqori sifatli variant
            $fileId   = $photo->file_id;
            $fileType = 'photo';
            $fileSize = $photo->file_size;
        } elseif ($message->document) {
            $fileId   = $message->document->file_id;
            $fileType = 'document';
            $fileName = $message->document->file_name;
            $fileSize = $message->document->file_size;
        } elseif ($message->voice) {
            $fileId   = $message->voice->file_id;
            $fileType = 'voice';
            $fileSize = $message->voice->file_size;
        } elseif ($message->video) {
            $fileId   = $message->video->file_id;
            $fileType = 'video';
            $fileSize = $message->video->file_size;
        }

        if ($fileId) {
            SessionService::saveAttachment($ticketId, $fileId, $fileType, $sentBy, $fileName, $fileSize);
        }
    }

    private static function saveMessage(
        \SergiX44\Nutgram\Telegram\Types\Message\Message $message,
        int $ticketId,
        string $sentBy,
        int $operatorId
    ): void {
        $body = $message->text ?? $message->caption;
        $type = 'text';

        if ($message->photo) {
            $type = 'photo';
            $body = $body ?: '[photo]';
        } elseif ($message->document) {
            $type = 'document';
            $body = $body ?: ('[document] '.($message->document->file_name ?? ''));
        } elseif ($message->voice) {
            $type = 'voice';
            $body = $body ?: '[voice]';
        } elseif ($message->video) {
            $type = 'video';
            $body = $body ?: '[video]';
        }

        SessionService::saveMessage(
            ticketId: $ticketId,
            sentBy: $sentBy,
            message: $body,
            messageType: $type,
            operatorId: $operatorId,
            telegramActorId: $operatorId,
            telegramMessageId: $message->message_id ?? null
        );
    }

    private static function notifyOtherOperators(Nutgram $bot, object $ticket, int $takenByOpId): void
    {
        $operators   = SessionService::getOperators();
        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
        foreach ($operators as $opId) {
            if ($opId === $takenByOpId) continue;
            try {
                $bot->sendMessage("ℹ️ Ticket #{$ticket->id} ($userDisplay) qabul qilindi.", chat_id: $opId);
            } catch (\Throwable $e) {
                Log::warning("[Operator] bildirishnomada xato", ['op_id' => $opId, 'error' => $e->getMessage()]);
            }
        }
    }
}
