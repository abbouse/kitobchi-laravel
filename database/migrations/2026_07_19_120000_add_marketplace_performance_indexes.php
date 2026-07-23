<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE PERFORMANCE — asosiy read indekslari.
 *
 * Muammo: books / stationeries / sellers jadvallarida faqat PRIMARY (+FULLTEXT)
 * indeks bor edi. Har bir katalog/ro'yxat so'rovi (status+is_approved+is_hidden
 * filtri, seller filtri, kategoriya, tartiblash) TO'LIQ JADVAL SKANI qilardi.
 *
 * Bu migratsiya eng ko'p ishlatiladigan so'rov shakllariga mos kompozit
 * indekslar qo'shadi. Read-heavy marketplace uchun yozuv narxi arziydi.
 *
 * Idempotent: mavjud indeks bo'lsa o'tkazib yuboriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── books ──────────────────────────────────────────────
        $this->addIndex('books', 'books_visibility_new_idx', ['is_approved', 'is_hidden', 'status', 'created_at']);
        $this->addIndex('books', 'books_visibility_trend_idx', ['is_approved', 'is_hidden', 'status', 'totalSalesWeek']);
        $this->addIndex('books', 'books_seller_idx', ['seller_id', 'is_hidden', 'is_approved']);
        $this->addIndex('books', 'books_category_idx', ['category_id', 'is_approved', 'is_hidden', 'status']);
        $this->addIndex('books', 'books_recommended_idx', ['recommended', 'is_approved', 'is_hidden']);

        // ── stationeries ───────────────────────────────────────
        $this->addIndex('stationeries', 'stat_visibility_new_idx', ['is_approved', 'is_hidden', 'status', 'created_at']);
        $this->addIndex('stationeries', 'stat_visibility_trend_idx', ['is_approved', 'is_hidden', 'status', 'totalSalesWeek']);
        $this->addIndex('stationeries', 'stat_seller_idx', ['seller_id', 'is_hidden', 'is_approved']);
        $this->addIndex('stationeries', 'stat_category_idx', ['category_id', 'is_approved', 'is_hidden', 'status']);
        $this->addIndex('stationeries', 'stat_recommended_idx', ['recommended', 'is_approved', 'is_hidden']);

        // ── gifts ──────────────────────────────────────────────
        $this->addIndex('gifts', 'gifts_seller_idx', ['seller_id', 'status', 'is_approved']);
        $this->addIndex('gifts', 'gifts_visibility_idx', ['status', 'is_approved', 'archived_at']);

        // ── sellers ────────────────────────────────────────────
        // whereHas('seller') faol sotuvchi tekshiruvi va sotuvchi ro'yxatlari
        $this->addIndex('sellers', 'sellers_active_idx', ['status', 'is_hidden', 'parent_id']);
        $this->addIndex('sellers', 'sellers_parent_idx', ['parent_id']);
    }

    public function down(): void
    {
        foreach ([
            'books' => ['books_visibility_new_idx', 'books_visibility_trend_idx', 'books_seller_idx', 'books_category_idx', 'books_recommended_idx'],
            'stationeries' => ['stat_visibility_new_idx', 'stat_visibility_trend_idx', 'stat_seller_idx', 'stat_category_idx', 'stat_recommended_idx'],
            'gifts' => ['gifts_seller_idx', 'gifts_visibility_idx'],
            'sellers' => ['sellers_active_idx', 'sellers_parent_idx'],
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
