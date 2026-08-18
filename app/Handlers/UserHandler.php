<?php

namespace App\Handlers;

use App\Services\SessionService;
use App\Services\TelegramSupportService;
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
                ? "🟢 <b>Operator siz bilan bog'langan.</b>"
                : "⏳ <b>Navbatdasiz:</b> {$pos}-o'rinda";

            $bot->sendMessage(
                "👋 <b>Assalomu alaykum!</b>\n\n" .
                "Sizning faol murojaatingiz mavjud:\n" .
                "🎫 Ticket: #<b>{$ticket->id}</b>\n" .
                "Holat: $statusText\n\n" .
                "Xabaringizni yuborishingiz mumkin. Murojaatni yakunlash uchun /cancel buyrug'ini bosing.",
                parse_mode: 'HTML'
            );
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make("📦 Mening buyurtmam", callback_data: "user_faq_order"),
                InlineKeyboardButton::make("🚚 Yetkazib berish", callback_data: "user_faq_delivery")
            )
            ->addRow(
                InlineKeyboardButton::make("💳 To'lov va Karta", callback_data: "user_faq_payment"),
                InlineKeyboardButton::make("🎁 Keshbek tizimi", callback_data: "user_faq_cashback")
            )
            ->addRow(
                InlineKeyboardButton::make("📲 Mobil ilova", callback_data: "user_faq_app"),
                InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator")
            );

        $bot->sendMessage(
            "👋 <b>Assalomu alaykum! Kitobchi Support xizmatiga xush kelibsiz.</b>\n\n" .
            "Quyidagi bo'limlardan keraklisini tanlang yoki to'g'ridan-to'g'ri savolingizni yozib qoldiring:\n\n" .
            "📎 <i>Matn, rasm, ovozli xabar, dumaloq video, hujjat — barchasini yuborishingiz mumkin.</i>",
            parse_mode: 'HTML',
            reply_markup: $keyboard
        );
    }

    public static function handleFaqOrder(Nutgram $bot): void
    {
        $cid = $bot->chatId();
        $orders = SessionService::getCustomerOrders($cid, 3);

        if (!empty($orders)) {
            $text = "📦 <b>Sizning oxirgi buyurtmalaringiz:</b>\n\n";
            foreach ($orders as $o) {
                $text .= "🔹 <b>Buyurtma #{$o['id']}</b>\n";
                $text .= "  • Summa: <b>{$o['amount']}</b>\n";
                $text .= "  • Holati: <b>{$o['status']}</b>\n";
                $text .= "  • Sana: {$o['date']}\n\n";
            }
            $text .= "Buyurtma bo'yicha savolingiz bo'lsa, xabar sifatida yozib yuborishingiz mumkin.";
        } else {
            $text = "📦 <b>Buyurtma holatini tekshirish:</b>\n\n" .
                "Buyurtmangiz holatini bilish uchun <b>buyurtma raqamini</b> yoki ro'yxatdan o'tgan <b>telefon raqamingizni</b> yozib yuboring (Masalan: <code>+998901234567</code> yoki <code>#1045</code>).\n\n" .
                "Operatorlarimiz darhol tekshirib berishadi!";
        }

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator"),
            InlineKeyboardButton::make("⬅️ Bosh menyu", callback_data: "user_faq_menu")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleFaqDelivery(Nutgram $bot): void
    {
        $text = "🚚 <b>Yetkazib berish xizmati haqida:</b>\n\n" .
            "• <b>Toshkent shahri bo'yicha:</b> 24 soat ichida eshikkacha yetkaziladi.\n" .
            "• <b>Viloyat va tuman markazlariga:</b> 2-3 ish kuni ichida ishonchli kurerlik/pochta orqali yetkaziladi.\n\n" .
            "💰 <i>Yetkazib berish narxi siz tanlagan manzil va hajmga qarab savatchada avtomatik hisoblanadi.</i>";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator"),
            InlineKeyboardButton::make("⬅️ Bosh menyu", callback_data: "user_faq_menu")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleFaqPayment(Nutgram $bot): void
    {
        $text = "💳 <b>To'lov va Karta ulash yo'riqnomasi:</b>\n\n" .
            "Ilovamizda yoki saytimizda <b>Uzcard</b> va <b>Humo</b> kartalaringizni bemalol ulab, to'g'ridan-to'g'ri xavfsiz to'lov qilishingiz mumkin.\n\n" .
            "🔒 Barcha to'lovlar 100% himoyalangan va karta ma'lumotlaringiz maxfiy saqlanadi.\n" .
            "Agar to'lov o'tmay qolgan bo'lsa, kartangizda SMS-xabarnoma (3DS) yoqilganligini tekshiring yoki chek skrinshotini yuboring.";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator"),
            InlineKeyboardButton::make("⬅️ Bosh menyu", callback_data: "user_faq_menu")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleFaqCashback(Nutgram $bot): void
    {
        $text = "🎁 <b>Kitobchi Keshbek tizimi:</b>\n\n" .
            "Har bir amalga oshirgan kitob xaridingizdan shaxsiy balansingizga keshbek qaytadi.\n\n" .
            "✨ <b>Afzalliklari:</b>\n" .
            "• Yig'ilgan keshbeklarni keyingi kitob xaridlaringizda chegirma sifatida to'liq ishlatishingiz mumkin!\n" .
            "• Keshbek balansini ilovadagi Profil bo'limida kuzatib borasiz.";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator"),
            InlineKeyboardButton::make("⬅️ Bosh menyu", callback_data: "user_faq_menu")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleFaqApp(Nutgram $bot): void
    {
        $text = "📲 <b>Kitobchi rasmiy mobil ilovasi:</b>\n\n" .
            "Kitoblar xarid qilish, audio kitoblarni tinglash va keshbek to'plash uchun ilovamizni yuklab oling:\n\n" .
            "🍏 <a href=\"https://apps.apple.com/uz/app/kitobchi/id6753818078\">App Store orqali yuklash (iOS)</a>\n" .
            "🤖 <a href=\"https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi\">Google Play orqali yuklash (Android)</a>\n" .
            "🌐 <a href=\"https://kitobchi.com\">Rasmiy veb-sayt: kitobchi.com</a>";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("💬 Operatorga yozish", callback_data: "user_connect_operator"),
            InlineKeyboardButton::make("⬅️ Bosh menyu", callback_data: "user_faq_menu")
        );

        $bot->sendMessage($text, parse_mode: 'HTML', reply_markup: $keyboard);
    }

    public static function handleHelp(Nutgram $bot): void
    {
        $bot->sendMessage(
            "ℹ️ <b>Yordam bo'limi</b>\n\n" .
            "• /start — Bosh menyu va murojaat holati\n" .
            "• /cancel — Faol murojaatni bekor qilish\n" .
            "• /myid — Sizning Telegram ID raqamingiz\n\n" .
            "Savolingizni shunchaki xabar sifatida yozing, biz yordam beramiz! 💬",
            parse_mode: 'HTML'
        );
    }

    public static function handleCancel(Nutgram $bot): void
    {
        $cid    = $bot->chatId();
        $ticket = SessionService::getUserActiveTicket($cid);

        if (!$ticket) {
            $bot->sendMessage("ℹ️ Sizda hozir faol murojaat yo'q.");
            return;
        }

        $opId = $ticket->operator_id;
        SessionService::closeTicket($ticket->id, 'user_cancelled');
        SessionService::saveSystemMessage($ticket->id, "Foydalanuvchi murojaatni bekor qildi.");

        if ($opId) {
            try {
                $bot->sendMessage(
                    "🔴 <b>Mijoz murojaatni bekor qildi.</b>\n🎫 Ticket #{$ticket->id}",
                    chat_id: (int) $opId,
                    parse_mode: 'HTML'
                );
            } catch (\Throwable) {}
        }

        $bot->sendMessage("✅ <b>Murojaatingiz bekor qilindi.</b>\nYana savollaringiz bo'lsa, bemalol murojaat qiling! 👋", parse_mode: 'HTML');
    }

    public static function handleMessage(Nutgram $bot): void
    {
        $cid     = $bot->chatId();
        $message = $bot->message();
        if (!$message) return;

        $ticket = SessionService::getUserActiveTicket($cid);

        // Kontakt (telefon raqam) yuborilgan bo'lsa
        if ($message->contact) {
            $phone = $message->contact->phone_number;
            try {
                $cleanPhone = preg_replace('/[^\d]/', '', $phone);
                $matchedUser = \App\Models\User::where('telegram_id', $cid)
                    ->orWhere('phone_number', 'LIKE', "%$cleanPhone%")
                    ->first();
                if ($matchedUser && empty($matchedUser->telegram_id)) {
                    $matchedUser->update(['telegram_id' => $cid]);
                }
            } catch (\Throwable) {}
        }

        // 1. Yangi ticket ochish
        if (!$ticket) {
            $user = $bot->user();
            $name = trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));
            $text = $message->text ?? $message->caption ?? ($message->contact ? "📱 Telefon raqami: {$message->contact->phone_number}" : '[Media fayl]');

            try {
                $ticket = SessionService::createTicket(
                    userId: $cid,
                    firstMessage: $text,
                    username: $user?->username,
                    name: $name ?: null
                );

                if ($message->contact) {
                    SessionService::updateTicket($ticket->id, ['phone' => $message->contact->phone_number]);
                }

                $workingHoursNote = "";
                if (!SessionService::isWorkingHours()) {
                    $workingHoursNote = "\n\n🌙 <i>Eslatma: Hozir ish vaqtidan tashqari vaqt (09:00 - 22:00). Operatorlarimiz ertalab birinchi bo'lib javob berishadi.</i>";
                }

                $bot->sendMessage(
                    "✅ <b>Murojaatingiz qabul qilindi!</b>\n🎫 Ticket #<b>{$ticket->id}</b>\n\n" .
                    "Operatorlarimizga xabar berildi. Tez orada javob beramiz ⏳\n" .
                    "Qo'shimcha ma'lumotlarni yuborishingiz mumkin.{$workingHoursNote}\n\n/cancel — Bekor qilish",
                    parse_mode: 'HTML'
                );

                self::saveUserMessage($message, (int) $ticket->id, $cid);
                self::saveMediaAttachment($message, (int) $ticket->id, 'user');

                self::dispatchTicket($bot, (array) $ticket);
            } catch (\Throwable $e) {
                Log::error("[User] Ticket yaratishda xatolik", ['error' => $e->getMessage()]);
                $bot->sendMessage("⚠️ Murojaatni qabul qilishda xatolik yuz berdi. Iltimos, qayta urinib ko'ring.");
            }
            return;
        }

        // 2. Ticket Navbatda (Queue) holatida
        if ($ticket->status === SessionService::STATUS_QUEUE) {
            $pos = SessionService::getQueuePosition($ticket->id);
            $bot->sendMessage("📨 Xabaringiz qabul qilindi. Navbatda: <b>{$pos}-o'rinda</b> ⏳", parse_mode: 'HTML');

            self::saveUserMessage($message, (int) $ticket->id, $cid);
            self::saveMediaAttachment($message, (int) $ticket->id, 'user');

            // Online operatorlarga yangi xabar haqida yetkazish
            $operators = SessionService::getOperators();
            foreach ($operators as $opId) {
                if (SessionService::getOperatorStatus($opId) === SessionService::OP_OFFLINE) continue;
                try {
                    $bot->copyMessage(chat_id: $opId, from_chat_id: $cid, message_id: $message->message_id);
                } catch (\Throwable) {}
            }
            return;
        }

        // 3. Ticket Faol (Active) — Operator bilan suhbat
        if ($ticket->status === SessionService::STATUS_ACTIVE) {
            $opId = (int) $ticket->operator_id;

            // Chat action operatorga
            app(TelegramSupportService::class)->sendChatAction($opId, self::resolveChatAction($message));

            $savedMsg = self::saveUserMessage($message, (int) $ticket->id, $cid);
            self::saveMediaAttachment($message, (int) $ticket->id, 'user');

            try {
                $copied = $bot->copyMessage(
                    chat_id: $opId,
                    from_chat_id: $cid,
                    message_id: $message->message_id
                );

                // Operatorga borgan xabar ID sini saqlash (Reply-To bog'lanishi uchun!)
                if ($copied?->message_id) {
                    SessionService::saveMessage(
                        ticketId: (int) $ticket->id,
                        sentBy: 'user',
                        message: $message->text ?? $message->caption ?? '[Media]',
                        messageType: $savedMsg->message_type,
                        telegramActorId: $opId,
                        telegramMessageId: $copied->message_id,
                        isDelivered: true
                    );
                }
            } catch (\Throwable $e) {
                Log::error("[User] Operatorga nusxalashda xato", ['error' => $e->getMessage(), 'op_id' => $opId]);
            }
        }
    }

    public static function dispatchTicket(Nutgram $bot, array $ticket): void
    {
        $operators   = SessionService::getOperators();
        $userDisplay = SessionService::formatUser($ticket['name'] ?? null, $ticket['username'] ?? null, (int) ($ticket['user_id'] ?? 0));
        $preview     = mb_substr($ticket['first_msg'] ?? '[Media]', 0, 150);
        $time        = \Carbon\Carbon::parse($ticket['created_at'] ?? now())->format('H:i');

        $text  = "🆕 <b>Yangi murojaat #{$ticket['id']}</b>\n\n";
        $text .= "👤 Mijoz: $userDisplay\n";
        $text .= "🕐 Vaqt: $time\n\n";
        $text .= "💬 <i>" . htmlspecialchars($preview) . "</i>";

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("✋ Qabul qilish", callback_data: "take_ticket:{$ticket['id']}")
        );

        foreach ($operators as $opId) {
            $status = SessionService::getOperatorStatus($opId);
            if ($status === SessionService::OP_OFFLINE || $status === SessionService::OP_BREAK) continue;

            try {
                $bot->sendMessage($text, chat_id: $opId, parse_mode: 'HTML', reply_markup: $keyboard);
            } catch (\Throwable $e) {
                Log::warning("[User] Operatorga bildirishnoma xatosi", ['op_id' => $opId, 'error' => $e->getMessage()]);
            }
        }
    }

    public static function sendRatingRequest(Nutgram $bot, int $userId, int $ticketId): void
    {
        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("⭐ 1", callback_data: "rate:$ticketId:1"),
            InlineKeyboardButton::make("⭐ 2", callback_data: "rate:$ticketId:2"),
            InlineKeyboardButton::make("⭐ 3", callback_data: "rate:$ticketId:3"),
            InlineKeyboardButton::make("⭐ 4", callback_data: "rate:$ticketId:4"),
            InlineKeyboardButton::make("⭐ 5", callback_data: "rate:$ticketId:5")
        );

        try {
            $bot->sendMessage(
                "✅ <b>Murojaat yakunlandi.</b>\n\nIltimos, ko'rsatilgan xizmat sifatini baholang 👇",
                chat_id: $userId,
                parse_mode: 'HTML',
                reply_markup: $keyboard
            );
        } catch (\Throwable) {}
    }

    public static function handleRatingCallback(Nutgram $bot): void
    {
        $parts    = explode(':', $bot->callbackQuery()->data);
        $ticketId = (int) ($parts[1] ?? 0);
        $score    = (int) ($parts[2] ?? 0);
        $cid      = $bot->chatId();

        $ticket = SessionService::getTicket($ticketId);
        if (!$ticket || (int) $ticket->user_id !== $cid) {
            $bot->answerCallbackQuery(text: "Xatolik!");
            return;
        }

        if ($ticket->rating) {
            $bot->answerCallbackQuery(text: "Siz allaqachon baholagansiz!");
            return;
        }

        SessionService::updateTicket($ticketId, [
            'rating' => $score,
            'status' => SessionService::STATUS_RATED,
        ]);

        if ($ticket->operator_id) {
            SessionService::addRating((int) $ticket->operator_id, $score);
            $stars = str_repeat('⭐', $score) . str_repeat('☆', 5 - $score);
            try {
                $bot->sendMessage(
                    "🌟 <b>Yangi baho:</b> $stars ($score/5)\n🎫 Ticket #{$ticketId}\n👤 " .
                    SessionService::formatUser($ticket->name, $ticket->username, $ticket->user_id),
                    chat_id: (int) $ticket->operator_id,
                    parse_mode: 'HTML'
                );
            } catch (\Throwable) {}
        }

        $stars = str_repeat('⭐', $score) . str_repeat('☆', 5 - $score);
        try {
            $bot->editMessageText(
                "✅ <b>Bahoyingiz qabul qilindi!</b>\n$stars ($score/5)\n\nFikringiz biz uchun muhim. Rahmat! 🙏",
                parse_mode: 'HTML'
            );
        } catch (\Throwable) {}

        $bot->answerCallbackQuery(text: "Rahmat! ⭐ $score");
    }

    // ─── Ichki yordamchilar ───────────────────────────────────────────────────

    private static function resolveChatAction(\SergiX44\Nutgram\Telegram\Types\Message\Message $message): string
    {
        if ($message->photo) return 'upload_photo';
        if ($message->voice) return 'record_voice';
        if ($message->video_note) return 'record_video_note';
        if ($message->video) return 'upload_video';
        if ($message->document) return 'upload_document';
        return 'typing';
    }

    private static function saveUserMessage(
        \SergiX44\Nutgram\Telegram\Types\Message\Message $message,
        int $ticketId,
        int $userId
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
            sentBy: 'user',
            message: $body,
            messageType: $type,
            telegramActorId: $userId,
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
