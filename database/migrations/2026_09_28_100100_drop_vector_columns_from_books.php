<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `books` dan vektor ustunlarini olib tashlash.
 *
 * Oldingi migratsiya ularni `book_edition_vectors` ga ko'chirdi. Alohida
 * migratsiya — ko'chirish muvaffaqiyatli tugamaguncha ustunlar o'chmasin.
 *
 * DEPLOYDAN KEYIN: `php artisan queue:restart`. Eski kod bilan ishlab turgan
 * navbat ishchisi `books.vectorData` ga yozmoqchi bo'lib xato beradi.
 *
 * Kanselyariyaga (`stationeries`) tegilmaydi — unda global katalog yo'q, har
 * mahsulot o'z vektoriga ega bo'lishi to'g'ri.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('books', 'books_has_vector_index')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropIndex('books_has_vector_index');
            });
        }

        $columns = array_values(array_filter(
            ['vectorData', 'vector_text_hash', 'has_vector'],
            fn (string $column) => Schema::hasColumn('books', $column)
        ));

        if ($columns !== []) {
            Schema::table('books', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    /**
     * Qaytarish: ustunlar tiklanadi va kartadagi vektor har bir taklifga
     * qaytariladi (hash NULL — eski kod ularni qayta hisoblaydi).
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (! Schema::hasColumn('books', 'vectorData')) {
                $table->json('vectorData')->nullable();
            }
            if (! Schema::hasColumn('books', 'vector_text_hash')) {
                $table->string('vector_text_hash', 32)->nullable();
            }
            if (! Schema::hasColumn('books', 'has_vector')) {
                $table->boolean('has_vector')->default(false);
            }
        });

        if (! $this->indexExists('books', 'books_has_vector_index')) {
            Schema::table('books', function (Blueprint $table) {
                $table->index('has_vector', 'books_has_vector_index');
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('book_edition_vectors')) {
            DB::statement("
                UPDATE books b
                JOIN book_edition_vectors v ON v.edition_id = b.edition_id
                SET b.vectorData = v.vector, b.has_vector = 1, b.vector_text_hash = NULL
            ");
        }
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
