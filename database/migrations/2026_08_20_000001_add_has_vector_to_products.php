<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tezlik audit (2026-08-20): `vectorReady()` / `vectorNeedsSync()` scope'lari
 * har bir so'rovda `whereRaw('JSON_LENGTH(vectorData) = 1536')` ishlatardi —
 * bu funksiya ustunga qo'llanganda MySQL indeksdan foydalana olmaydi va har
 * safar butun jadvalni skan qiladi (full table scan). Jadval kattalashgani
 * sayin bu `vectors:rebuild` (10 daqiqada bir marta) va qidiruv indeksini
 * isitish (`reading-intelligence:warm-search-index`) uchun asosiy yuk
 * manbalaridan biriga aylanadi.
 *
 * Yechim: yozish paytida (ProductVectorService) hisoblab qo'yiladigan oddiy
 * indekslangan boolean ustun — `has_vector`. Endi scope'lar
 * `WHERE has_vector = 1` kabi oddiy, indeksdan foydalanadigan shart bilan
 * ishlaydi, JSON_LENGTH har so'rovda qayta hisoblanmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'has_vector')) {
                    $t->boolean('has_vector')->default(false)->after('vector_text_hash');
                }
            });

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! $this->indexExists($table, $table . '_has_vector_index')) {
                    $t->index('has_vector', $table . '_has_vector_index');
                }
            });
        }

        // ── Bir martalik backfill — mavjud vectorData'ga qarab has_vector
        //    ni to'g'irlaymiz. Bu faqat migratsiya paytida bir marta
        //    ishlaydi (har so'rovda emas), shuning uchun JSON_LENGTH
        //    shu yerda ishlatilishi muammo emas.
        foreach (['books', 'stationeries'] as $table) {
            DB::statement("
                UPDATE `{$table}`
                SET has_vector = (vectorData IS NOT NULL AND JSON_LENGTH(vectorData) = 1536)
            ");
        }
    }

    public function down(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if ($this->indexExists($table, $table . '_has_vector_index')) {
                    $t->dropIndex($table . '_has_vector_index');
                }
                if (Schema::hasColumn($table, 'has_vector')) {
                    $t->dropColumn('has_vector');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($rows) > 0;
    }
};
