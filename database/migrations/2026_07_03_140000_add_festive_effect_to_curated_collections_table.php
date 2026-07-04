<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curated_collections')) {
            return;
        }

        Schema::table('curated_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('curated_collections', 'festive_effect')) {
                // Hero orqasidagi bayramona yulduzcha animatsiyasi yoqilgan/yo'q
                $table->boolean('festive_effect')->default(true)->after('custom_total_price');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('curated_collections') && Schema::hasColumn('curated_collections', 'festive_effect')) {
            Schema::table('curated_collections', function (Blueprint $table) {
                $table->dropColumn('festive_effect');
            });
        }
    }
};
