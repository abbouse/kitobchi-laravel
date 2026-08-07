<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * READING INTELLIGENCE — PERFORMANCE INDEKSLARI.
 *
 * Item sahifasidagi "bu menga mosmi?" kartochkasi sekin yuklanishining
 * ildiz sababini tekshirganda (foydalanuvchi so'ragan tezlik auditi)
 * ikkita indekssiz og'ir so'rov aniqlandi:
 *
 *   1. `solds` — `categoryCollaborativeStats()` (ReadingIntelligenceService)
 *      har bir KATEGORIYA uchun (soatiga bir marta, keshlangan)
 *      `WHERE completed_at IS NOT NULL ORDER BY completed_at DESC LIMIT 2000`
 *      so'rovini bajaradi. Jadvalda `completed_at`ga alohida indeks yo'q
 *      edi (faqat `user_id+completed_at` bor edi — bu yerga foydasiz,
 *      chunki so'rov user_id bo'yicha filtrlanmaydi) — demak har safar
 *      BUTUN jadval skanerlanib, keyin filesort qilinardi. Jadval vaqt
 *      o'tishi bilan faqat kattalashadi, shuning uchun bu eng xavfli
 *      nuqta edi.
 *
 *   2. `books` / `stationeries` — `categoryRank()`, `categoryCentroidVector()`,
 *      `similarSection()`ning xarid-tarixi/eng-ko'p-sotilgan zaxiralari va
 *      `RecommendBooksPush` buyrug'i barchasi `ORDER BY totalSales DESC`
 *      ishlatadi (ba'zan `category_id` filtri bilan, ba'zan umuman
 *      filtrsiz). Mavjud indekslarda faqat `totalSalesWeek` bor edi
 *      (`totalSales` emas) — shuning uchun bu so'rovlar mos indeksdan
 *      foydalana olmay, filesort qilardi.
 *
 * Idempotent: mavjud indeks bo'lsa o'tkazib yuboriladi (mavjud performance
 * migratsiyalaridagi bir xil naqsh).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── solds ──────────────────────────────────────────────
        // Kategoriya-darajasidagi kollaborativ statistika: butun
        // buyurtmalar tarixi bo'ylab (user filtrisiz) eng so'nggi 2000
        // tasini olish uchun.
        $this->addIndex('solds', 'solds_completed_at_idx', ['completed_at']);

        // ── books ──────────────────────────────────────────────
        $this->addIndex('books', 'books_category_sales_idx', ['category_id', 'is_approved', 'is_hidden', 'status', 'totalSales']);
        $this->addIndex('books', 'books_visibility_sales_idx', ['is_approved', 'is_hidden', 'status', 'totalSales']);

        // ── stationeries ───────────────────────────────────────
        $this->addIndex('stationeries', 'stat_category_sales_idx', ['category_id', 'is_approved', 'is_hidden', 'status', 'totalSales']);
        $this->addIndex('stationeries', 'stat_visibility_sales_idx', ['is_approved', 'is_hidden', 'status', 'totalSales']);
    }

    public function down(): void
    {
        foreach ([
            'solds' => ['solds_completed_at_idx'],
            'books' => ['books_category_sales_idx', 'books_visibility_sales_idx'],
            'stationeries' => ['stat_category_sales_idx', 'stat_visibility_sales_idx'],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndex($table, $index);
            }
        }
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return; // ustun yo'q bo'lsa indeks yaratmaymiz
            }
        }
        if ($this->indexExists($table, $name)) {
            return;
        }
        Schema::table($table, function ($t) use ($name, $columns) {
            $t->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            Schema::table($table, fn ($t) => $t->dropIndex($name));
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $result = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$name]);

        return ! empty($result);
    }
};
