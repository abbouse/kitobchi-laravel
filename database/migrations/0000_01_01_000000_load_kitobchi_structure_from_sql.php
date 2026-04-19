<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * kitobchi.sql dagi barcha jadvallar — CREATE TABLE bloklari
 * database/schema/kitobchi_structure.sql faylida (skript bilan generatsiya).
 *
 * Yangilash: php database/scripts/extract_kitobchi_create_tables.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // InnoDB / MySQL 8 sintaksisi — faqat MySQL (masalan, sqlite testda o‘tkazib yuboriladi).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $path = database_path('schema/kitobchi_structure.sql');

        if (! File::exists($path)) {
            throw new RuntimeException(
                "Schema fayli yo‘q: {$path}. Avval ishga tushiring: php database/scripts/extract_kitobchi_create_tables.php"
            );
        }

        $sql = File::get($path);

        if ($sql === '' || $sql === false) {
            throw new RuntimeException("Schema fayli bo‘sh: {$path}");
        }

        DB::unprepared($sql);
    }

    public function down(): void
    {
        // Dumpdan yuklangan to‘liq sxema — migrate:rollback bilan xavfsiz tashlamaymiz.
    }
};
