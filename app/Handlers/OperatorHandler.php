<?php

namespace App\Handlers;

use App\Services\SessionService;
use App\Services\SupportChatBridgeService;
use App\Services\TelegramSupportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class OperatorHandler
{
    public static function isOperator(Nutgram $bot, int $chatId): bool
    {
        return SessionService::isOperator($chatId);
    }

    public static function handleStart(Nutgram $bot): void
    {
        $cid    = $bot->chatId();
        $status = SessionService::getOperatorStatus($cid);
        $ticket = SessionService::getOperatorActiveTicket($cid);
        $queue  = SessionService::getQueue();

        $emoji = match ($status) {
            SessionService::OP_BUSY    => '🟡',
            SessionService::OP_BREAK   => '☕',
            SessionService::OP_OFFLINE => '🔴',
            default                    => '🟢',
        };

        $text  = "👨‍💻 <b>Kitobchi Support — Operator Paneli</b>\n\n";
        $text .= "$emoji Status: <b>" . strtoupper($status) . "</b>\n";
        $text .= "🆔 Operator ID: <code>$cid</code>\n";
        $text .= "📋 Navbatda: <b>" . count($queue) . " ta</b> murojaat\n";

        if ($ticket) {
            $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
            $text .= "\n🎫 <b>Faol suhbat:</b> #{$ticket->id}\n👤 Mijoz: $userDisplay\n";
            $text .= "💡 <i>Xabar yozing yoki /end bilan yakunlang.</i>\n";
        } else {
            $text .= "\n<i>Sizda hozir faol suhbat yo'q. Yangi murojaatni qabul qilish uchun quyidagi tugmalardan foydalaning.</i>\n";
        }

        $inlineKeyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make("🟢 Online", callback_data: "op_status:online"),
                InlineKeyboardButton::make("☕ Tanaffus", callback_data: "op_status:break"),
                InlineKeyboardButton::make("🔴 Offline", callback_data: "op_status:offline")
            )
            ->addRow(
                InlineKeyboardButton::make("📋 Navbat (" . count($queue) . ")", callback_data: "op_view_queue"),
                InlineKeyboardButton::make("⚡ Shablonlar", callback_data: "op_canned_list")
            );

        if ($ticket) {
            $inlineKeyboard->addRow(
                InlineKeyboardButton::make("🔴 Suhbatni yopish", callback_data: "confirm_close:{$ticket->id}"),
                InlineKeyboardButton::make("🔁 Boshqa operatorga", callback_data: "transfer_ticket:{$ticket->id}")
            );
        } elseif (!empty($queue)) {
            $inlineKeyboard->addRow(
                InlineKeyboardButton::make("✋ Navbatdagini qabul qilish", callback_data: "take_ticket:{$queue[0]->id}")
            );
        }

        // Qulay Reply klaviatura
        $replyKeyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(
                KeyboardButton::make("📋 Navbat"),
                KeyboardButton::make("🎫 Faol murojaat")
            )
            ->addRow(
                KeyboardButton::make("⚡ Tezkor javoblar"),
                KeyboardButton::make("📊 Statistika"),
                KeyboardButton::make("⚙️ Status")
            );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $inlineKeyboard);
    }

    public static function handleHelp(Nutgram $bot): void
    {
        $bot->sendMessage(
            "📖 <b>Operator uchun qo'llanma</b>\n\n" .
            "<b>Asosiy buyruqlar:</b>\n" .
            "• /start — Bosh panel va status\n" .
            "• /queue — Navbatdagi murojaatlar ro'yxati\n" .
            "• /take — Navbatdagi birinchi ticketni qabul qilish\n" .
            "• /current — Faol murojaat ma'lumotlari\n" .
            "• /quick yoki /shablon — Tezkor javob shablonlari\n" .
            "• /end — Joriy suhbatni yopish\n" .
            "• /status — Ish holatini o'zgartirish (Online/Tanaffus/Offline)\n" .
            "• /stats — Shaxsiy ko'rsatkichlar va baholar\n" .
            "• /history [ticket_id] — Ilovalar va fayllar tarixi\n\n" .
            "<b>💡 Maslahat:</b>\n" .
            "Foydalanuvchining kelgan xabariga Telegramda <b>\"Reply\" (Javob berish)</b> orqali to'g'ridan-to'g'ri javob qaytarishingiz mumkin!",
            parse_mode: 'HTML'
        );
    }

    public static function handleQueue(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $queue = SessionService::getQueue();
        if (empty($queue)) {
            $bot->sendMessage("📭 <b>Navbat bo'sh!</b>\nHozirda kutilayotgan murojaatlar yo'q.", parse_mode: 'HTML');
            return;
        }

        $text = "📋 <b>Kutilayotgan murojaatlar ro'yxati (" . count($queue) . " ta):</b>\n\n";
        $keyboard = InlineKeyboardMarkup::make();

        foreach (array_slice($queue, 0, 8) as $i => $ticket) {
            $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
            $preview     = mb_substr($ticket->first_msg ?? '[Media]', 0, 70);
            $time        = \Carbon\Carbon::parse($ticket->created_at)->format('H:i');

            $text .= ($i + 1) . ". 🎫 <b>#{$ticket->id}</b> — $userDisplay <i>($time)</i>\n";
            $text .= "   💬 <i>" . htmlspecialchars($preview) . "</i>\n\n";

            $keyboard->addRow(
                InlineKeyboardButton::make("✋ #{$ticket->id} ni qabul qilish", callback_data: "take_ticket:{$ticket->id}")
            );
        }

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleTake(Nutgram $bot, ?int $targetTicketId = null): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $active = SessionService::getOperatorActiveTicket($cid);
        if ($active) {
            $bot->sendMessage(
                "⚠️ <b>Sizda allaqachon faol murojaat bor:</b> Ticket #{$active->id}\n\nYangi murojaat olishdan oldin uni /end orqali yoping yoki boshqa operatorga o'tkazing.",
                parse_mode: 'HTML'
            );
            return;
        }

        if ($targetTicketId) {
            $ticket = SessionService::getTicket($targetTicketId);
        } else {
            $queue  = SessionService::getQueue();
            $ticket = $queue[0] ?? null;
        }

        if (!$ticket) {
            $bot->sendMessage("📭 Navbatda hech qanday murojaat yo'q.");
            return;
        }

        if ($ticket->status !== SessionService::STATUS_QUEUE) {
            $bot->sendMessage("⚠️ Bu murojaat (#{$ticket->id}) allaqachon boshqa operator tomonidan qabul qilingan.");
            return;
        }

        $assigned = SessionService::assignOperator($ticket->id, $cid);
        if (!$assigned) {
            $bot->sendMessage("⚠️ Bu ticketni qabul qilib bo'lmadi (boshqa operator ulangan bo'lishi mumkin).");
            return;
        }

        self::notifyAssignment($bot, (int) $ticket->id, $cid);
    }

    public static function handleCurrent(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticket = SessionService::getOperatorActiveTicket($cid);
        if (!$ticket) {
            $bot->sendMessage("ℹ️ Sizda hozir faol murojaat yo'q.\n/queue orqali navbatni ko'rishingiz mumkin.");
            return;
        }

        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);
        $time        = \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y H:i');

        $text  = "🎫 <b>Faol murojaat #{$ticket->id}</b>\n\n";
        $text .= "👤 Mijoz: $userDisplay\n";
        $text .= "🆔 Telegram ID: <code>{$ticket->user_id}</code>\n";
        $text .= "🕐 Boshlangan: $time\n";
        $text .= "📝 Dastlabki xabar: <i>" . htmlspecialchars($ticket->first_msg ?? '') . "</i>\n\n";
        $text .= "Xabar yozsangiz, to'g'ridan-to'g'ri mijozga yetkaziladi.";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make("⚡ Tezkor javob", callback_data: "op_canned_list"),
                InlineKeyboardButton::make("📎 Tarix / Ilovalar", callback_data: "op_history:{$ticket->id}")
            )
            ->addRow(
                InlineKeyboardButton::make("🔁 O'tkazish (Transfer)", callback_data: "transfer_ticket:{$ticket->id}"),
                InlineKeyboardButton::make("🔴 Suhbatni yopish", callback_data: "confirm_close:{$ticket->id}")
            );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleEnd(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticket = SessionService::getOperatorActiveTicket($cid);
        if (!$ticket) {
            $bot->sendMessage("ℹ️ Sizda hozir faol murojaat yo'q.");
            return;
        }

        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("✅ Ha, yopish", callback_data: "confirm_close:{$ticket->id}"),
            InlineKeyboardButton::make("❌ Bekor qilish", callback_data: "cancel_close")
        );

        $bot->sendMessage(
            "🔴 <b>Murojaatni yopishni tasdiqlaysizmi?</b>\n\n🎫 Ticket: #{$ticket->id}\n👤 Mijoz: $userDisplay\n\nYopilgandan so'ng mijozga xizmat sifatini baholash so'rovi yuboriladi.",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        );
    }

    public static function handleStatus(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $current = SessionService::getOperatorStatus($cid);
        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("🟢 Online", callback_data: "op_status:online"),
            InlineKeyboardButton::make("☕ Tanaffus", callback_data: "op_status:break"),
            InlineKeyboardButton::make("🔴 Offline", callback_data: "op_status:offline")
        );

        $bot->sendMessage(
            "Joriy ish statusi: <b>" . strtoupper($current) . "</b>\n\nYangi statusni tanlang:",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        );
    }

    public static function handleOpStatusCallback(Nutgram $bot): void
    {
        $data   = $bot->callbackQuery()->data;
        $status = explode(':', $data)[1] ?? 'online';
        $cid    = $bot->chatId();

        if (!self::isOperator($bot, $cid)) {
            $bot->answerCallbackQuery(text: "Ruxsat berilmagan!");
            return;
        }

        SessionService::setOperatorStatus($cid, $status);
        $emoji = match ($status) {
            SessionService::OP_BREAK => '☕',
            SessionService::OP_OFFLINE => '🔴',
            default => '🟢',
        };

        $bot->answerCallbackQuery(text: "$emoji Status: " . ucfirst($status));
        $bot->sendMessage("$emoji Sizning status yangilandi: <b>" . strtoupper($status) . "</b>", parse_mode: 'HTML');
    }

    public static function handleStats(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $stats = SessionService::getStats($cid);
        $stars = $stats->avg_rating > 0
            ? str_repeat('⭐', (int) round($stats->avg_rating)) . " ({$stats->avg_rating} / 5)"
            : "Hali baholanmagan";

        $text  = "📊 <b>Sizning ish ko'rsatkichlaringiz:</b>\n\n";
        $text .= "📥 Qabul qilingan murojaatlar: <b>{$stats->handled}</b> ta\n";
        $text .= "✅ Yopilgan murojaatlar: <b>{$stats->closed}</b> ta\n";
        $text .= "🌟 O'rtacha mijoz bahosi: <b>$stars</b>\n";
        $text .= "📝 Baholagan mijozlar soni: <b>{$stats->total_rated}</b> ta\n";

        $bot->sendMessage($text, parse_mode: 'HTML');
    }

    public static function handleQuickReplies(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticket = SessionService::getOperatorActiveTicket($cid);
        $replies = SessionService::getQuickReplies();

        $text = "⚡ <b>Tezkor javob shablonlari:</b>\n\n";
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($replies as $key => $item) {
            $text .= "<b>{$item['title']}:</b>\n<i>" . htmlspecialchars($item['text']) . "</i>\n\n";
            if ($ticket) {
                $keyboard->addRow(
                    InlineKeyboardButton::make("➡️ {$item['title']} yuborish", callback_data: "op_canned_send:{$key}")
                );
            }
        }

        if (!$ticket) {
            $text .= "<i>💡 Faol suhbat mavjud bo'lganda ushbu shablonlarni 1 tugma orqali mijozga yuborishingiz mumkin.</i>";
        }

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleSendQuickReply(Nutgram $bot, string $key): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticket = SessionService::getOperatorActiveTicket($cid);
        if (!$ticket) {
            $bot->answerCallbackQuery(text: "Faol murojaat topilmadi!");
            return;
        }

        $replies = SessionService::getQuickReplies();
        $reply = $replies[$key] ?? null;
        if (!$reply) {
            $bot->answerCallbackQuery(text: "Shablon topilmadi!");
            return;
        }

        $userId = (int) $ticket->user_id;
        $body = $reply['text'];

        if ($ticket->source_type === 'shop_chat' && $ticket->source_conversation_id) {
            app(SupportChatBridgeService::class)->sendReplyToConversation($ticket, $body, $cid);
        } else {
            app(TelegramSupportService::class)->sendChatAction($userId, 'typing');
            $res = app(TelegramSupportService::class)->sendText($userId, $body, parse_mode: null);

            SessionService::saveMessage(
                ticketId: (int) $ticket->id,
                sentBy: 'operator',
                message: $body,
                messageType: 'text',
                operatorId: $cid,
                telegramActorId: $cid,
                telegramMessageId: data_get($res, 'result.message_id'),
                isDelivered: true
            );
        }

        $bot->answerCallbackQuery(text: "✅ Shablon yuborildi!");
        $bot->sendMessage("✅ <b>Yuborildi:</b>\n" . htmlspecialchars($body), parse_mode: 'HTML');
    }

    public static function handleHistory(Nutgram $bot, ?string $ticketIdParam = null): void
    {
        $cid = $bot->chatId();
        if (!self::isOperator($bot, $cid)) return;

        $ticketId = $ticketIdParam ? (int) $ticketIdParam : null;
        if (!$ticketId) {
            $active = SessionService::getOperatorActiveTicket($cid);
            if (!$active) {
                $bot->sendMessage("Foydalanish: /history <code>[ticket_id]</code>", parse_mode: 'HTML');
                return;
            }
            $ticketId = (int) $active->id;
        }

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket) {
            $bot->sendMessage("❌ Ticket #$ticketId topilmadi.");
            return;
        }

        $attachments = SessionService::getTicketAttachments($ticketId);
        if (empty($attachments)) {
            $bot->sendMessage("📭 Ticket #$ticketId da saqlangan fayl/ilova yo'q.");
            return;
        }

        $bot->sendMessage("📎 <b>Ticket #$ticketId ilovalari (" . count($attachments) . " ta):</b>", parse_mode: 'HTML');

        foreach ($attachments as $att) {
            $sentByLabel = $att->sent_by === 'user' ? '👤 Mijoz' : '👨‍💻 Operator';
            $time        = \Carbon\Carbon::parse($att->created_at)->format('d.m H:i');
            $caption     = "$sentByLabel · $time" . ($att->file_name ? "\n📄 {$att->file_name}" : "");

            try {
                match ($att->file_type) {
                    'photo'      => $bot->sendPhoto(photo: $att->file_id, chat_id: $cid, caption: $caption),
                    'document'   => $bot->sendDocument(document: $att->file_id, chat_id: $cid, caption: $caption),
                    'voice'      => $bot->sendVoice(voice: $att->file_id, chat_id: $cid, caption: $caption),
                    'video'      => $bot->sendVideo(video: $att->file_id, chat_id: $cid, caption: $caption),
                    'video_note' => $bot->sendVideoNote(video_note: $att->file_id, chat_id: $cid),
                    'audio'      => $bot->sendAudio(audio: $att->file_id, chat_id: $cid, caption: $caption),
                    'sticker'    => $bot->sendSticker(sticker: $att->file_id, chat_id: $cid),
                    default      => $bot->sendMessage("📎 Fayl turi: {$att->file_type}", chat_id: $cid),
                };
            } catch (\Throwable $e) {
                Log::warning("[Operator] History yuborishda xato", ['file_id' => $att->file_id, 'error' => $e->getMessage()]);
            }
        }
    }

    public static function handleTakeCallback(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) (explode(':', $data)[1] ?? 0);
        $cid      = $bot->chatId();

        if (!self::isOperator($bot, $cid)) {
            $bot->answerCallbackQuery(text: "Sizda operator ruxsati yo'q!");
            return;
        }

        $active = SessionService::getOperatorActiveTicket($cid);
        if ($active && (int)$active->id !== $ticketId) {
            $bot->answerCallbackQuery(text: "Avval faol Ticket #{$active->id} ni yoping!", show_alert: true);
            return;
        }

        $assigned = SessionService::assignOperator($ticketId, $cid);
        if (!$assigned) {
            $bot->answerCallbackQuery(text: "Bu ticket allaqachon boshqa operator tomonidan qabul qilingan!", show_alert: true);
            try {
                $bot->editMessageText("⚠️ Ushbu ticket boshqa operator tomonidan qabul qilindi.");
            } catch (\Throwable) {}
            return;
        }

        $bot->answerCallbackQuery(text: "✅ Ticket #$ticketId qabul qilindi!");
        self::notifyAssignment($bot, $ticketId, $cid);
    }

    public static function handleConfirmClose(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) (explode(':', $data)[1] ?? 0);
        $cid      = $bot->chatId();

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || ((int) $ticket->operator_id !== $cid && !SessionService::isAdmin($cid))) {
            $bot->answerCallbackQuery(text: "Ruxsat berilmagan!");
            return;
        }

        SessionService::closeTicket($ticketId, 'operator_closed');
        SessionService::saveSystemMessage($ticketId, "Operator suhbatni yakunladi.");

        $bot->answerCallbackQuery(text: "✅ Murojaat yopildi!");
        $bot->sendMessage("✅ <b>Ticket #$ticketId muvaffaqiyatli yopildi.</b>\nStatusingiz qayta 🟢 ONLINE qilindi.", parse_mode: 'HTML');

        if ($ticket->source_type !== 'shop_chat') {
            UserHandler::sendRatingRequest($bot, (int) $ticket->user_id, $ticketId);
        }
    }

    public static function handleTransferCallback(Nutgram $bot): void
    {
        $data     = $bot->callbackQuery()->data;
        $ticketId = (int) (explode(':', $data)[1] ?? 0);
        $cid      = $bot->chatId();

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || ((int) $ticket->operator_id !== $cid && !SessionService::isAdmin($cid))) {
            $bot->answerCallbackQuery(text: "Ruxsat berilmagan!");
            return;
        }

        SessionService::updateTicket($ticketId, [
            'status'      => SessionService::STATUS_QUEUE,
            'operator_id' => null,
        ]);
        SessionService::setOperatorStatus($cid, SessionService::OP_ONLINE);
        SessionService::saveSystemMessage($ticketId, "Operator ticketni umumiy navbatga qaytardi.");

        $bot->answerCallbackQuery(text: "Ticket navbatga qaytarildi");
        $bot->sendMessage("🔄 <b>Ticket #$ticketId umumiy navbatga qaytarildi.</b>", parse_mode: 'HTML');

        UserHandler::dispatchTicket($bot, (array) $ticket);
    }

    /**
     * Operator tomonidan yuborilgan har qanday xabarni mijozga yo'naltirish (Dual-Routing).
     */
    public static function handleMessage(Nutgram $bot): void
    {
        $cid     = $bot->chatId();
        $message = $bot->message();
        if (!$message) return;

        // 1. Reply-to orqali yoki Faol sessiya orqali ticketni topish
        $ticket = null;
        if ($message->reply_to_message) {
            $ticket = SessionService::findTicketByReplyMessage($cid, (int) $message->reply_to_message->message_id);
        }

        if (!$ticket) {
            $ticket = SessionService::getOperatorActiveTicket($cid);
        }

        if (!$ticket) {
            $queue = SessionService::getQueue();
            $keyboard = InlineKeyboardMarkup::make();
            if (!empty($queue)) {
                $keyboard->addRow(
                    InlineKeyboardButton::make("✋ Navbatdagi ticketni qabul qilish", callback_data: "take_ticket:{$queue[0]->id}")
                );
            }
            $bot->sendMessage(
                "ℹ️ <b>Sizda hozir faol suhbat yo'q.</b>\n\n" .
                "• /queue orqali navbatdagi murojaatni qabul qiling\n" .
                "• Yoki mijoz xabariga <b>Reply</b> qilib javob yozing.",
                parse_mode: 'HTML',
                reply_markup: $keyboard
            );
            return;
        }

        // Shop chat bridge integratsiyasi
        if ($ticket->source_type === 'shop_chat' && $ticket->source_conversation_id) {
            $body = $message->text ?? $message->caption ?? '[Media xabar]';
            self::saveMediaAttachment($message, (int) $ticket->id, 'operator');
            app(SupportChatBridgeService::class)->sendReplyToConversation($ticket, $body, $cid);
            $bot->sendMessage("✅ Xabar mijozga yetkazildi.");
            return;
        }

        $userId = (int) $ticket->user_id;

        // Chat Action (yozmoqda...)
        app(TelegramSupportService::class)->sendChatAction($userId, self::resolveChatAction($message));

        // Saqlash va Foydalanuvchiga yuborish
        $savedMessage = self::saveOperatorMessage($message, (int) $ticket->id, $cid);
        self::saveMediaAttachment($message, (int) $ticket->id, 'operator');

        try {
            // Agar oddiy matn bo'lsa
            if ($message->text) {
                $response = app(TelegramSupportService::class)->sendText($userId, $message->text);
                $deliveredId = (int) data_get($response, 'result.message_id');
                $savedMessage->update([
                    'is_delivered'        => true,
                    'delivery_error'      => null,
                    'telegram_message_id' => $deliveredId ?: null,
                ]);
            } else {
                // Media (rasm, ovoz, dumaloq video, video, hujjat, stiker, lokatsiya, kontakt)
                $copied = $bot->copyMessage(
                    chat_id: $userId,
                    from_chat_id: $cid,
                    message_id: $message->message_id
                );
                $savedMessage->update([
                    'is_delivered'        => true,
                    'delivery_error'      => null,
                    'telegram_message_id' => $copied?->message_id,
                ]);
            }

            Log::info("[Operator] Xabar mijozga yetkazildi", ['ticket_id' => $ticket->id, 'user_id' => $userId]);
        } catch (\Throwable $e) {
            $savedMessage->update([
                'is_delivered'   => false,
                'delivery_error' => $e->getMessage(),
            ]);
            Log::error("[Operator] Xabar yuborish xatosi", ['error' => $e->getMessage(), 'ticket_id' => $ticket->id]);
            $bot->sendMessage("⚠️ <b>Xabar yetkazilmadi!</b>\nMijoz botni bloklagan bo'lishi mumkin: " . htmlspecialchars($e->getMessage()), parse_mode: 'HTML');
        }
    }

    // ─── Yordamchi metodlar ───────────────────────────────────────────────────

    private static function notifyAssignment(Nutgram $bot, int $ticketId, int $opId): void
    {
        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket) return;

        SessionService::saveSystemMessage($ticketId, "Operator suhbatga ulandi.");
        $userDisplay = SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make("⚡ Shablonlar", callback_data: "op_canned_list"),
                InlineKeyboardButton::make("📎 Ilovalar", callback_data: "op_history:$ticketId")
            )
            ->addRow(
                InlineKeyboardButton::make("🔁 O'tkazish", callback_data: "transfer_ticket:$ticketId"),
                InlineKeyboardButton::make("🔴 Suhbatni yopish", callback_data: "confirm_close:$ticketId")
            );

        $text  = "✅ <b>Ticket #$ticketId qabul qilindi!</b>\n\n";
        $text .= "👤 Mijoz: $userDisplay\n";
        $text .= "🆔 ID: <code>{$ticket->user_id}</code>\n\n";
        $text .= "📝 Dastlabki xabar:\n<i>" . htmlspecialchars($ticket->first_msg ?? '') . "</i>\n\n";
        $text .= "💬 Endi yozgan barcha xabarlaringiz mijozga yetkaziladi.";

        $bot->sendMessage($text, chat_id: $opId, parse_mode: 'HTML', reply_markup: $keyboard);

        // Mijozga xabar berish
        if ($ticket->source_type !== 'shop_chat') {
            try {
                $bot->sendMessage(
                    "🟢 <b>Operator siz bilan bog'landi!</b>\nSavolingizni yoki xabaringizni yozishingiz mumkin.",
                    chat_id: (int) $ticket->user_id,
                    parse_mode: 'HTML'
                );
            } catch (\Throwable) {}
        }
    }

    private static function resolveChatAction(\SergiX44\Nutgram\Telegram\Types\Message\Message $message): string
    {
        if ($message->photo) return 'upload_photo';
        if ($message->voice) return 'record_voice';
        if ($message->video_note) return 'record_video_note';
        if ($message->video) return 'upload_video';
        if ($message->document) return 'upload_document';
        return 'typing';
    }

    private static function saveOperatorMessage(
        \SergiX44\Nutgram\Telegram\Types\Message\Message $message,
        int $ticketId,
        int $operatorId
    ): \App\Models\BotTicketMessage {
        $type = 'text';
        $body = $message->text ?? $message->caption ?? null;

        if ($message->photo) {
            $type = 'photo';
            $body = $body ?: '[Rasm]';
        } elseif ($message->voice) {
            $type = 'voice';
            $body = $body ?: '[Ovozli xabar]';
        } elseif ($message->video_note) {
            $type = 'video_note';
            $body = $body ?: '[Dumaloq video]';
        } elseif ($message->video) {
            $type = 'video';
            $body = $body ?: '[Video]';
        } elseif ($message->document) {
            $type = 'document';
            $body = $body ?: ('[Hujjat] ' . ($message->document->file_name ?? ''));
        } elseif ($message->audio) {
            $type = 'audio';
            $body = $body ?: '[Audio]';
        } elseif ($message->sticker) {
            $type = 'sticker';
            $body = $body ?: '[Stiker ' . ($message->sticker->emoji ?? '') . ']';
        } elseif ($message->location) {
            $type = 'location';
            $body = "Lat: {$message->location->latitude}, Lon: {$message->location->longitude}";
        } elseif ($message->contact) {
            $type = 'contact';
            $body = "Tel: {$message->contact->phone_number} ({$message->contact->first_name})";
        }

        return SessionService::saveMessage(
            ticketId: $ticketId,
            sentBy: 'operator',
            message: $body,
            messageType: $type,
            operatorId: $operatorId,
            telegramActorId: $operatorId,
            telegramMessageId: $message->message_id,
            isDelivered: true
        );
    }

    private static function saveMediaAttachment(
        \SergiX44\Nutgram\Telegram\Types\Message\Message $message,
        int $ticketId,
        string $sentBy
    ): void {
        $fileId   = null;
        $fileType = null;
        $fileName = null;
        $fileSize = null;

        if ($message->photo) {
            $photo    = end($message->photo);
            $fileId   = $photo->file_id;
            $fileType = 'photo';
            $fileSize = $photo->file_size;
        } elseif ($message->voice) {
            $fileId   = $message->voice->file_id;
            $fileType = 'voice';
            $fileSize = $message->voice->file_size;
        } elseif ($message->video_note) {
            $fileId   = $message->video_note->file_id;
            $fileType = 'video_note';
            $fileSize = $message->video_note->file_size;
        } elseif ($message->video) {
            $fileId   = $message->video->file_id;
            $fileType = 'video';
            $fileSize = $message->video->file_size;
        } elseif ($message->document) {
            $fileId   = $message->document->file_id;
            $fileType = 'document';
            $fileName = $message->document->file_name;
            $fileSize = $message->document->file_size;
        } elseif ($message->audio) {
            $fileId   = $message->audio->file_id;
            $fileType = 'audio';
            $fileName = $message->audio->file_name;
            $fileSize = $message->audio->file_size;
        } elseif ($message->sticker) {
            $fileId   = $message->sticker->file_id;
            $fileType = 'sticker';
            $fileSize = $message->sticker->file_size;
        }

        if ($fileId) {
            SessionService::saveAttachment($ticketId, $fileId, $fileType, $sentBy, $fileName, $fileSize);
        }
    }
}
