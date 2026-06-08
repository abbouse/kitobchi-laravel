<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('books') && Schema::hasColumn('books', 'kangaroo_listing_score')) {
            DB::statement('ALTER TABLE books MODIFY kangaroo_listing_score DECIMAL(2,1) NULL');
        }

        if (Schema::hasTable('stationeries') && Schema::hasColumn('stationeries', 'kangaroo_listing_score')) {
            DB::statement('ALTER TABLE stationeries MODIFY kangaroo_listing_score DECIMAL(2,1) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('books') && Schema::hasColumn('books', 'kangaroo_listing_score')) {
            DB::statement('ALTER TABLE books MODIFY kangaroo_listing_score TINYINT UNSIGNED NULL');
        }

        if (Schema::hasTable('stationeries') && Schema::hasColumn('stationeries', 'kangaroo_listing_score')) {
            DB::statement('ALTER TABLE stationeries MODIFY kangaroo_listing_score TINYINT UNSIGNED NULL');
        }
    }
};
