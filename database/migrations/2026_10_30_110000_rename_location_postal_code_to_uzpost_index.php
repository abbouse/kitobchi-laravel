<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manzil pochta indeksi ustuni nomi: locations.uzpost_index.
 * Oldingi migratsiya `postal_code` nomi bilan ishga tushgan bo'lsa — qayta nomlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locations') || Schema::hasColumn('locations', 'uzpost_index')) {
            return;
        }
        if (Schema::hasColumn('locations', 'postal_code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->renameColumn('postal_code', 'uzpost_index');
            });

            return;
        }
        Schema::table('locations', function (Blueprint $table) {
            $table->string('uzpost_index', 6)->nullable()->index();
        });
    }

    public function down(): void
    {
        // Ataylab bo'sh: ustun 2026_10_30_100000 migratsiyasida o'chiriladi
    }
};
