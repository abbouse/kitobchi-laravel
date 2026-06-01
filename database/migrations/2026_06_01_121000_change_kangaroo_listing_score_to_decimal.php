<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE books MODIFY kangaroo_listing_score DECIMAL(2,1) NULL');
        DB::statement('ALTER TABLE stationeries MODIFY kangaroo_listing_score DECIMAL(2,1) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE books MODIFY kangaroo_listing_score TINYINT UNSIGNED NULL');
        DB::statement('ALTER TABLE stationeries MODIFY kangaroo_listing_score TINYINT UNSIGNED NULL');
    }
};
