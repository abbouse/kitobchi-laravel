<?php

namespace App\Handlers;

use App\Services\SessionService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class UserHandler
{
    public static function handleStart(Nutgram $bot): void
    {
        $cid    = $bot->chatId();
        $ticket = SessionService::getUserActiveTicket($cid);

        if ($ticket) {
            $pos = $ticket->status === SessionService::STATUS_QUEUE
                ? SessionService::getQueuePosition($ticket->id)
                : 0;

            $statusText = $ticket->status === SessionService::STATUS_ACTIVE
                ? "✅ Operator siz bilan gaplashmoqda"
                : "⏳ Navbatda: {$pos}-o'rinda";

            $bot->sendMessage(
                "👋 Salom!\n\nFaol murojaatingiz:\n🎫 Ticket #{$ticket->id}\n$statusText\n\nXabar yuboring yoki /cancel bilan yoping.",
                parse_mode: 'HTML'
            );
            Log::info("[User] faol ticket bor", ['ticket_id' => $ticket->id]);
            return;
        }

        $bot->sendMessage(
            "👋 Salom! <b>Support xizmatiga xush kelibsiz.</b>\n\n" .
            "Savolingizni yozing — operator tez orada javob beradi.\n" .
            "Matn, rasm, fayl, ovoz — hammasi qabul qilinadi 📎",
            parse_mode: 'HTML'
        );
        Log::info("[User] yangi foydalanuvchi /start", ['user_id' => $cid]);
    }

    public static function handleHelp(Nutgram $bot): void
    {
        $bot->sendMessage(
            "ℹ️ <b>Yordam</b>\n\n/start — Bosh menyu\n/cancel — Murojaatni yopish\n/myid — ID ko'rish\n\nSavolingizni yozing, biz yordam beramiz! 💬",
            parse_mode: 'HTML'
        );
    }

    public static function handleCancel(Nutgram $bot): void
    {
        $cid    = $bot->chatId();
        $ticket = SessionService::getUserActiveTicket($cid);

        if (!$ticket) {
            $bot->sendMessage("Sizda faol murojaat yo'q.");
            return;
        }

        $opId = $ticket->operator_id;
        SessionService::closeTicket($ticket->id, 'user_cancelled');

        if ($opId) {
            try {
                $bot->sendMessage(
                    "🔴 Foydalanuvchi murojaatni yopdi.\n🎫 Ticket #{$ticket->id}",
                    chat_id: $opId,
                    parse_mode: 'HTML'
                );
            } catch (\Throwable $e) {
                Log::warning("[User] operator ga yopilish xabari yuborilmadi", ['error' => $e->getMessage()]);
            }
        }

        $bot->sendMessage("✅ Murojaatingiz yopildi. Yana savol bo'lsa yozing! 👋");
        Log::info("[User] ticket yopildi (user tomonidan)", ['ticket_id' => $ticket->id]);
    }

    public static function handleMessage(Nutgram $bot): void
    {
        $cid     = $bot->chatId();
        $message = $bot->message();
        if (!$message) return;

        Log::info("[User] xabar keldi", [
            'user_id' => $cid,
            'type'    => $message->getType(),
            'text'    => $message->text ?? $message->caption ?? '[media]',
        ]);

        $ticket = SessionService::getUserActiveTicket($cid);
        Log::debug("[User] faol ticket", ['ticket_id' => $ticket ? $ticket->id : "yo'q"]);

        // Yangi ticket yaratish
        if (!$ticket) {
            Log::info("[User] yangi ticket yaratilmoqda");
            $user = $bot->user();
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $text = $message->text ?? $message->caption ?? '[media]';

            try {
                $ticket = SessionService::createTicket(
                    $cid,
                    $text,
                    $user->username ?? null,
                    $name ?: null
                );

                Log::info("[User] ticket yaratildi", ['ticket_id' => $ticket->id ?? 'xato']);

                $bot->sendMessage(
                    "✅ <b>Murojaatingiz qabul qilindi!</b>\n🎫 Ticket #<b>{$ticket->id}</b>\n\n" .
                    "Operator tez orada javob beradi ⏳\nQo'shimcha xabar yubora olasiz.\n\n/cancel — Yopish",
                    parse_mode: 'HTML'
                );

                // Birinchi xabar ilovasini saqlash
                self::saveAttachment($message, $ticket->id, 'user');

                self::dispatchTicket($bot, (array) $ticket);
            } catch (\Throwable $e) {
                Log::error("[User] ticket yaratish xatosi", [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ]);
                $bot->sendMessage("⚠️ Murojaatni qabul qilishda xatolik. Iltimos qayta urinib ko'ring.");
            }
            return;
        }

        // Navbatda — ilovani saqlash + operatorlarga forward
        if ($ticket->status === SessionService::STATUS_QUEUE) {
            $pos = SessionService::getQueuePosition($ticket->id);
            $bot->sendMessage("📨 Xabaringiz qabul qilindi. Navbatda: {$pos}-o'rinda ⏳");

            self::saveAttachment($message, $ticket->id, 'user');

            Log::info("[User] queue'da xabar forward qilinmoqda", ['ticket_id' => $ticket->id]);
            foreach (SessionService::getOperators() as $opId) {
                try {
                    $bot->copyMessage(chat_id: $opId, from_chat_id: $cid, message_id: $message->message_id);
                } catch (\Throwable $e) {
                    Log::warning("[User] operator ga forward xatosi", ['op_id' => $opId, 'error' => $e->getMessage()]);
                }
            }
            return;
        }

        if ($ticket->status !== SessionService::STATUS_ACTIVE) {
            $bot->sendMessage("Murojaatingiz yopilgan. Yangi savol uchun yozing.");
            return;
        }

        // Faol suhbat — ilovani saqlash + operatorga nusxalash
        $opId = (int) $ticket->operator_id;
        self::saveAttachment($message, $ticket->id, 'user');

        Log::info("[User] faol suhbatda xabar operatorga yuborilmoqda", ['ticket_id' => $ticket->id, 'to_op' => $opId]);
        try {
            $bot->copyMessage(chat_id: $opId, from_chat_id: $cid, message_id: $message->message_id);
            Log::info("[User] nusxalash muvaffaqiyatli");
        } catch (\Throwable $e) {
            Log::error("[User] operator ga forward xatosi", ['error' => $e->getMessage()]);
        }
    }

    public static function handleRatingCallback(Nutgram $bot): void
    {
        $parts    = explode(':', $bot->callbackQuery()->data);
        $ticketId = (int)($parts[1] ?? 0);
        $score    = (int)($parts[2] ?? 0);
        $cid      = $bot->chatId();

        Log::info("[User] baho callback", ['ticket_id' => $ticketId, 'score' => $score, 'user_id' => $cid]);

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || (int)$ticket->user_id !== $cid) {
            $bot->answerCallbackQuery(text: "❌ Xatolik");
            return;
        }

        if ($ticket->rating) {
            $bot->answerCallbackQuery(text: "Siz allaqachon baho berdingiz!");
            return;
        }

        if ($ticket->status !== SessionService::STATUS_CLOSED) {
            $bot->answerCallbackQuery(text: "Murojaat hali yopilmagan");
            return;
        }

        SessionService::updateTicket($ticketId, ['rating' => $score, 'status' => SessionService::STATUS_RATED]);

        if ($ticket->operator_id) {
            SessionService::addRating((int)$ticket->operator_id, $score);
            $stars = str_repeat('⭐', $score) . str_repeat('☆', 5 - $score);
            try {
                $bot->sendMessage(
                    "🌟 Baho: $stars ($score/5)\n🎫 Ticket #{$ticketId}\n👤 " .
                    SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id),
                    chat_id: (int)$ticket->operator_id,
                    parse_mode: 'HTML'
                );
            } catch (\Throwable $e) {
                Log::warning("[User] operator ga baho xabari yuborilmadi", ['error' => $e->getMessage()]);
            }
        }

        $stars = str_repeat('⭐', $score) . str_repeat('☆', 5 - $score);
        $bot->editMessageText(
            "✅ Bahoyingiz qabul qilindi!\n$stars ($score/5)\n\nRahmat! 🙏",
            chat_id: $cid,
            message_id: $bot->callbackQuery()->message->message_id,
            parse_mode: 'HTML'
        );

        $bot->answerCallbackQuery(text: "Rahmat! ⭐");
        Log::info("[User] baho qabul qilindi", ['ticket_id' => $ticketId, 'score' => $score]);
    }

    public static function dispatchTicket(Nutgram $bot, array $ticket): void
    {
        Log::info("[User] dispatchTicket boshlandi", ['ticket_id' => $ticket['id']]);

        $operators   = SessionService::getOperators();
        $userDisplay = SessionService::formatUser($ticket['name'], $ticket['username'], $ticket['user_id']);
        $preview     = mb_substr($ticket['first_msg'] ?? '', 0, 150);
        $time        = $ticket['created_at'] ?? now();

        $text =
            "🆕 <b>Yangi murojaat #{$ticket['id']}</b>\n" .
            "👤 $userDisplay\n" .
            "🕐 $time\n\n" .
            "📝 <i>" . htmlspecialchars($preview) . "</i>";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("✋ Qabul qilish", callback_data: "take_ticket:{$ticket['id']}")
        );

        $sent = 0;
        foreach ($operators as $opId) {
            if (SessionService::getOperatorStatus($opId) === SessionService::OP_OFFLINE) continue;
            try {
                $bot->sendMessage($text, chat_id: $opId, parse_mode: 'HTML', reply_markup: $keyboard);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning("[User] operator ga bildirishnoma xatosi", ['op_id' => $opId, 'error' => $e->getMessage()]);
            }
        }

        Log::info("[User] bildirishnomalar yuborildi", ['sent_to' => $sent, 'total_operators' => count($operators)]);
    }

    public static function sendRatingRequest(Nutgram $bot, int $userId, int $ticketId): void
    {
        Log::info("[User] baholash so'ralmoqda", ['user_id' => $userId, 'ticket_id' => $ticketId]);

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("⭐ 1", callback_data: "rate:$ticketId:1"),
            InlineKeyboardButton::make("⭐ 2", callback_data: "rate:$ticketId:2"),
            InlineKeyboardButton::make("⭐ 3", callback_data: "rate:$ticketId:3"),
            InlineKeyboardButton::make("⭐ 4", callback_data: "rate:$ticketId:4"),
            InlineKeyboardButton::make("⭐ 5", callback_data: "rate:$ticketId:5"),
        );

        try {
            $bot->sendMessage(
                "✅ <b>Murojaat yopildi.</b>\n\nXizmat sifatini baholang 👇",
                chat_id: $userId,
                parse_mode: 'HTML',
                reply_markup: $keyboard
            );
            Log::info("[User] baholash so'rovi yuborildi");
        } catch (\Throwable $e) {
            Log::warning("[User] baholash so'rovi yuborilmadi", ['error' => $e->getMessage()]);
        }
    }

    // ─── Ichki yordamchi ─────────────────────────────────────────────────────

    /**
     * Xabardan fayl ma'lumotini olib bot_ticket_attachments ga saqlaydi.
     * Matnli xabarlarda hech narsa saqlanmaydi.
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
}