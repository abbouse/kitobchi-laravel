<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pochta indeksi:
 *  - post_offices — O'zpochta bo'limlari (indeks + koordinata), `postal:sync-uzpost` to'ldiradi;
 *  - locations.uzpost_index — manzilga eng yaqin O'zpochta bo'limi indeksi (avtomatik).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_offices')) {
            Schema::create('post_offices', function (Blueprint $table) {
                $table->id();
                $table->string('postal_index', 6)->index();
                $table->decimal('lat', 10, 7);
                $table->decimal('lon', 10, 7);
                $table->string('name', 255)->nullable();
                $table->string('address', 500)->nullable();
                $table->timestamps();
                $table->index(['lat', 'lon']);
            });
        }

        if (Schema::hasTable('locations') && ! Schema::hasColumn('locations', 'uzpost_index')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('uzpost_index', 6)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'uzpost_index')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('uzpost_index');
            });
        }
        Schema::dropIfExists('post_offices');
    }
};
