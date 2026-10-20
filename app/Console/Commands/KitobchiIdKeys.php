<?php

namespace App\Console\Commands;

use App\Services\KitobchiId\KeyStore;
use Illuminate\Console\Command;

class KitobchiIdKeys extends Command
{
    protected $signature = 'kitobchi-id:keys
        {--rotate : Yangi kalit yaratib, uni joriy qilish (eskisi JWKS da qoladi)}
        {--prune : Joriy bo\'lmagan kalitlarni JWKS dan olib tashlash (rotatsiyadan kamida 1 soat keyin!)}';

    protected $description = 'Kitobchi ID imzolash kalitlari (RS256) — yaratish, rotatsiya, tozalash';

    public function handle(KeyStore $keys): int
    {
        if ($this->option('prune')) {
            $moved = $keys->prune();
            $this->info($moved ? 'Arxivga olindi: '.implode(', ', $moved) : 'Eski kalit yo\'q.');

            return self::SUCCESS;
        }

        if ($keys->hasActive() && ! $this->option('rotate')) {
            $this->info('Joriy kalit: '.$keys->active()['kid'].' (yangisi uchun --rotate)');

            return self::SUCCESS;
        }

        $kid = $keys->generate();
        $this->info('Yangi joriy kalit: '.$kid);
        $this->line('Katalog: '.$keys->path().' — veb-server foydalanuvchisi o\'qiy olishi, boshqalar o\'qiy olmasligi kerak.');

        return self::SUCCESS;
    }
}
