<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'kangaroo_listing_score')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('kangaroo_listing_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('books') && ! Schema::hasColumn('books', 'kangaroo_listing_score')) {
            Schema::table('books', function (Blueprint $table) {
                $table->decimal('kangaroo_listing_score', 2, 1)->nullable()->after('kangaroo_listing_decision');
            });
        }

        if (Schema::hasTable('stationeries') && ! Schema::hasColumn('stationeries', 'kangaroo_listing_score')) {
            Schema::table('stationeries', function (Blueprint $table) {
                $table->decimal('kangaroo_listing_score', 2, 1)->nullable()->after('kangaroo_listing_decision');
            });
        }
    }
};
