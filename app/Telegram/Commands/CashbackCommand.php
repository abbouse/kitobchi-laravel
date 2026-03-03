<?php

namespace App\Telegram\Commands;

use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use App\Models\User;

class CashbackCommand extends Command
{
    protected string $command = 'cashback';
    protected ?string $description = 'Foydalanuvchi keshbek miqdorini ko\'rish';

    public function handle(Nutgram $bot): void
    {
        $userId = $bot->message()->from->id;
        $user = User::where('telegram_id', $userId)->first();

        if (!$user) {
            $bot->sendMessage("⚠️ Siz hali ro'yxatdan o'tmagansiz! Avval telefon raqamingizni yuboring.");
            return;
        }

        if ($user->id > 0) {
            $bot->sendMessage("💸 Sizning keshbekingiz: *{$user->id} so'm*", parse_mode: 'markdown');
        } else {
            $bot->sendMessage("❌ Sizda hali keshbek mavjud emas.");
        }
    }
}
