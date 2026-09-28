<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VEKTOR KITOB KARTASIGA KO'CHADI.
 *
 * Ilgari embedding har bir do'kon TAKLIFIDA (`books.vectorData`) saqlanardi.
 * Bitta kitobni 20 ta do'kon sotsa — 20 ta vektor, 20 ta OpenAI chaqiruvi,
 * 20 barobar xotira. Yana: embed matniga do'kon nomi, narx va ANIQ sotuv soni
 * qo'shilardi, shuning uchun har sotuvda matn o'zgarib, vektor qayta yasalardi.
 *
 * Endi vektor kitobning o'ziga tegishli: `book_edition_vectors` (1:1,
 * `edition_id` bo'yicha). Alohida jadval — chunki bitta vektor ~30KB JSON;
 * `book_editions` ga ustun qilib qo'yilsa, kartani yuklaydigan har so'rov
 * (admin katalog, buy box, qidiruv natijalari) shu og'irlikni tortardi —
 * `books` jadvalida aynan shu muammo bor edi (HasBranchStock uni ataylab
 * chetlab o'tardi).
 *
 * Sotuv statistikasi ham kartada: `sales_week`, `sales_total` — kitobning
 * BARCHA do'konlardagi sotuvi. Bozor ro'yxatlari har kitobdan bitta taklifni
 * ko'rsatadi, ular kitobni shu umumiy son bo'yicha saralaydi (ilgari faqat
 * tanlangan bitta do'konning sotuvi hisobga olinardi).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_edition_vectors')) {
            Schema::create('book_edition_vectors', function (Blueprint $table) {
                $table->unsignedBigInteger('edition_id')->primary();
                $table->json('vector');
                // Embed matnining md5 — matn o'zgarmasa OpenAI'ga qayta borilmaydi.
                // NULL — "qayta hisoblash kerak" belgisi.
                $table->char('text_hash', 32)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('book_editions', function (Blueprint $table) {
            if (! Schema::hasColumn('book_editions', 'sales_week')) {
                $table->unsignedInteger('sales_week')->default(0)->after('min_price');
            }
            if (! Schema::hasColumn('book_editions', 'sales_total')) {
                $table->unsignedInteger('sales_total')->default(0)->after('sales_week');
            }
        });

        // Bozor ro'yxatlari kartani shu ikki son bo'yicha saralaydi.
        if (! $this->indexExists('book_editions', 'book_editions_sales_idx')) {
            Schema::table('book_editions', function (Blueprint $table) {
                $table->index(['sales_week', 'sales_total'], 'book_editions_sales_idx');
            });
        }

        $this->copyExistingVectors();
        $this->seedSalesTotals();
    }

    /**
     * Mavjud vektorlarni kartaga ko'chiramiz — deploy paytida semantik qidiruv
     * bo'sh qolmasin. Har kartaga uning tanlangan taklifidagi vektor olinadi.
     *
     * `text_hash = NULL`: bu vektorlar eski formatdagi matndan (do'kon, narx,
     * sotuv soni bilan) yasalgan. Rejalashtirilgan `vectors:rebuild` ularni
     * asta-sekin toza kitob matni bilan qayta hisoblaydi; shungacha eskisi
     * qidiruvda to'liq ishlayveradi.
     */
    private function copyExistingVectors(): void
    {
        if (! Schema::hasColumn('books', 'vectorData')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // INSERT IGNORE birinchi kiritilganini saqlaydi; ORDER BY tanlangan
            // taklifni oldinga qo'yadi.
            DB::statement("
                INSERT IGNORE INTO book_edition_vectors (edition_id, vector, text_hash, created_at, updated_at)
                SELECT b.edition_id, b.vectorData, NULL, NOW(), NOW()
                FROM books b
                WHERE b.edition_id IS NOT NULL
                  AND b.vectorData IS NOT NULL
                  AND b.has_vector = 1
                ORDER BY b.catalog_featured DESC, b.id ASC
            ");

            return;
        }

        DB::table('books')
            ->whereNotNull('edition_id')
            ->whereNotNull('vectorData')
            ->orderByDesc('catalog_featured')
            ->orderBy('id')
            ->select(['edition_id', 'vectorData'])
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('book_edition_vectors')->insertOrIgnore([
                        'edition_id' => $row->edition_id,
                        'vector' => $row->vectorData,
                        'text_hash' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Umumiy sotuv — takliflar yig'indisi. `sales_week` bu yerda to'ldirilmaydi:
     * `books.totalSalesWeek` hech qachon nolga qaytmagan (haftalik emas), uni
     * `catalog:sales-stats` buyurtmalardan haqiqiy 7 kunlik oyna bilan hisoblaydi.
     */
    private function seedSalesTotals(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            UPDATE book_editions e
            JOIN (
                SELECT edition_id, SUM(COALESCE(totalSales, 0)) AS total
                FROM books
                WHERE edition_id IS NOT NULL
                GROUP BY edition_id
            ) s ON s.edition_id = e.id
            SET e.sales_total = s.total
        ");
    }

    public function down(): void
    {
        if ($this->indexExists('book_editions', 'book_editions_sales_idx')) {
            Schema::table('book_editions', function (Blueprint $table) {
                $table->dropIndex('book_editions_sales_idx');
            });
        }

        Schema::table('book_editions', function (Blueprint $table) {
            foreach (['sales_week', 'sales_total'] as $column) {
                if (Schema::hasColumn('book_editions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('book_edition_vectors');
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
