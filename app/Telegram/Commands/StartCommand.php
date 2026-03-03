<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;

use App\Models\User;

class StartCommand extends Command
{
    protected string $command = 'start';
    protected ?string $description = 'Start command';

    public function handle(Nutgram $bot): void
    {
        $firstName = $bot->user()->first_name;
        $userId = $bot->userId();
        $existingUser = User::where('telegram_id', $userId)->first();
            
            
            if ($existingUser) {
                $bot->sendMessage(
                    text: "Assalomu alaykum $firstName!\n\nXaridlarni boshlash vaqti keldi",
                    parse_mode: 'markdown',
                    reply_markup: ReplyKeyboardMarkup::make(
                        resize_keyboard: true
                    )
                    ->addRow(
                        KeyboardButton::make(
                            text: '🛒 Xaridlarni boshlash',
                            web_app: new WebAppInfo(
                                url: 'https://bookwormappbot.vercel.app/?phone_number='.$existingUser->phone_number.'&token='.$existingUser->remember_token
                            )
                        )
                    )
                    ->addRow(
                        KeyboardButton::make(text: '📦 Xaridlarim'),
                        KeyboardButton::make(text: '💸 Keshbek')
                    )
                );
            } else {
                $bot->sendMessage(
                    text: "Assalomu alaykum $firstName!\n\nBotdan foydalanish uchun iltimos telefon raqamingizni men bilan ulashing!",
                    parse_mode: 'markdown',
                    reply_markup: ReplyKeyboardMarkup::make(
                        resize_keyboard: true
                    )
                    ->addRow(
                        KeyboardButton::make(
                            text: '📱 Telefon raqamni yuborish',
                            request_contact: true
                        )
                    )
                );
            }
    }

}
