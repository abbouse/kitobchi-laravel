<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;
use App\Models\User;
use Illuminate\Support\Str;

class GetContactCommand extends Command
{
    protected string $command = 'getcontact';
    protected ?string $description = 'Telefon raqamini olish';

    public function handle(Nutgram $bot): void
    {
        if ($bot->message()->from->is_bot) {
            return;
        }

        $contact = $bot->message()->contact;
        $userId = $bot->message()->from->id;

        if (!$contact) {
            $bot->sendMessage("⚠️ Telefon raqamingizni yuboring!");
            return;
        }

        // Foydalanuvchi o‘zining raqamini yuborishi kerak
        if ($contact->user_id != $userId) {
            $bot->sendMessage("⚠️ Iltimos, o‘zingizning raqamingizni yuboring!");
            return;
        }

        $checkUser = User::where('telegram_id', $userId)->first();
        $name = $contact->first_name ?? $contact->phone_number;
        $phone = str_replace('+', '', $contact->phone_number);
        $token = Str::random(60);

        $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(
                KeyboardButton::make(
                    text: '🛒 Xaridlarni boshlash',
                    web_app: new WebAppInfo(
                        url: 'https://bookwormappbot.vercel.app/?phone_number='.$phone.'&token='.$token
                    )
                )
            )
            ->addRow(
                KeyboardButton::make(text: '📦 Xaridlarim'),
                KeyboardButton::make(text: '💸 Keshbek')
            );

        if (!$checkUser) {
            $newUser = User::create([
                'telegram_id' => $userId,
                'phone_number' => $phone,
                'name' => $name,
                'lastname' => $contact->last_name ?? 'TG',
                'remember_token' => $token,
            ]);

            if ($newUser) {
                $bot->sendMessage(
                    text: "Assalomu alaykum, $name!\n\nXaridlarni boshlash vaqti keldi!",
                    parse_mode: 'markdown',
                    reply_markup: $keyboard
                );
            } else {
                $bot->sendMessage("❌ Server xatosi yuz berdi, iltimos qaytadan urinib ko‘ring.");
            }
        } else {
            $bot->sendMessage(
                text: "✅ Ushbu raqam avval ro‘yxatdan o‘tgan. Xaridlarni davom ettirishingiz mumkin!\n\n/start buyrug‘ini yuboring.",
                parse_mode: 'markdown',
                reply_markup: $keyboard
            );
        }
    }
}
