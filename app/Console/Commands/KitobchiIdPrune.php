<?php

namespace App\Console\Commands;

use App\Models\KitobchiId\IdAuthCode;
use App\Models\KitobchiId\IdRefreshToken;
use Illuminate\Console\Command;

class KitobchiIdPrune extends Command
{
    protected $signature = 'kitobchi-id:prune';

    protected $description = 'Muddati o\'tgan Kitobchi ID kodlari va refresh tokenlarini o\'chirish';

    public function handle(): int
    {
        $codes = IdAuthCode::where('expires_at', '<', now()->subDay())->delete();
        // Bekor qilingan/ishlatilgan tokenlar 7 kun saqlanadi — qayta ishlatishni aniqlash uchun
        $tokens = IdRefreshToken::where(function ($q) {
            $q->where('family_expires_at', '<', now())
                ->orWhere('expires_at', '<', now()->subDays(7))
                ->orWhere('revoked_at', '<', now()->subDays(7));
        })->delete();
        // Ilovaga yuborilgan kodlarning shifrlangan nusxasi muddati o'tishi bilan o'chiriladi
        $scrubbed = \App\Services\KitobchiId\AppCodeChannel::scrubExpired();
        $this->info("Kodlar: $codes, refresh tokenlar: $tokens, chat kodlari tozalandi: $scrubbed");

        return self::SUCCESS;
    }
}
