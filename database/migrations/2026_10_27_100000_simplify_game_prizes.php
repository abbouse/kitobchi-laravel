<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Omadli maydon: boshqaruv soddalashtirildi — sovg'alarda muddat va qo'shimcha
 * shartlar yo'q (faqat yoqish/o'chirish va soni). Mavjud sovg'alardagi eski
 * qiymatlar tozalanadi, aks holda ular ko'rinmas holda ishlab turardi.
 * Promokod/tanga rasmlari ilovada avtomatik chiziladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_prizes')) {
            return;
        }
        DB::table('game_prizes')->update([
            'starts_at' => null,
            'ends_at' => null,
            'min_orders' => 0,
            'min_spins' => 0,
            'max_wins_per_user' => null,
            'daily_limit' => null,
            'valid_days' => 30,
        ]);
        DB::table('game_prizes')->whereIn('type', ['promocode', 'coins'])->update(['image' => null]);
    }

    public function down(): void
    {
        // Qaytarib bo'lmaydi (eski qiymatlar saqlanmagan)
    }
};
