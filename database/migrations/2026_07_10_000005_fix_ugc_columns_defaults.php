<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production bazasida ugc_* ustunlari default qiymatsiz yaratilgan —
 * MySQL strict rejimda yangi mahsulot insert qilishda
 * "Field 'ugc_reviews_count' doesn't have a default value" xatosi chiqadi.
 * Bu migratsiya ustunlarni asl ta'rifga (default 0) tenglashtiradi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            if (Schema::hasColumn($table, 'ugc_reviews_count')) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `ugc_reviews_count` INT UNSIGNED NOT NULL DEFAULT 0"
                );
            }

            if (Schema::hasColumn($table, 'ugc_aggregate_score')) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `ugc_aggregate_score` DECIMAL(3,2) NOT NULL DEFAULT 0"
                );
            }
        }
    }

    public function down(): void
    {
        // Default qo'shish xavfsiz o'zgarish — orqaga qaytarish shart emas
    }
};
