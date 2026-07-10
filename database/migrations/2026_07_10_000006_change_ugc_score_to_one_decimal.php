<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UGC reyting formati: bitta o'nlik xona (1.3, 4.5 ...).
 *
 * - ugc_aggregate_score: DECIMAL(2,1) — 1.30 emas, 1.3 ko'rinishida.
 *   Avval mavjud qiymatlar 1 xonaga yaxlitlanadi (strict rejimda MODIFY
 *   paytida truncation xatosi chiqmasligi uchun), keyin ustun torayadi.
 * - ugc_reviews_count: INT UNSIGNED DEFAULT 0 — izohlar SONI, decimal emas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            if (Schema::hasColumn($table, 'ugc_aggregate_score')) {
                // 1. Mavjud qiymatlarni bitta o'nlik xonaga yaxlitlash
                DB::statement("UPDATE `{$table}` SET `ugc_aggregate_score` = ROUND(`ugc_aggregate_score`, 1) WHERE `ugc_aggregate_score` IS NOT NULL");

                // 2. Ustunni DECIMAL(2,1) ga toraytirish (max 9.9 — 1..5 uchun yetarli)
                DB::statement("ALTER TABLE `{$table}` MODIFY `ugc_aggregate_score` DECIMAL(2,1) NOT NULL DEFAULT 0");
            }

            if (Schema::hasColumn($table, 'ugc_reviews_count')) {
                // Soni butun son bo'lishi shart
                DB::statement("ALTER TABLE `{$table}` MODIFY `ugc_reviews_count` INT UNSIGNED NOT NULL DEFAULT 0");
            }
        }
    }

    public function down(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            if (Schema::hasColumn($table, 'ugc_aggregate_score')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `ugc_aggregate_score` DECIMAL(3,2) NOT NULL DEFAULT 0");
            }
        }
    }
};
