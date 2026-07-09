<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'vector_text_hash')) {
                    $t->string('vector_text_hash', 32)->nullable()->after('vectorData');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['books', 'stationeries'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'vector_text_hash')) {
                    $t->dropColumn('vector_text_hash');
                }
            });
        }
    }
};
