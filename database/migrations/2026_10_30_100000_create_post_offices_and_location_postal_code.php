<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pochta indeksi:
 *  - post_offices — O'zpochta bo'limlari (indeks + koordinata), `postal:sync-uzpost` to'ldiradi;
 *  - locations.postal_code — manzilga eng yaqin bo'lim indeksi (avtomatik).
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

        if (Schema::hasTable('locations') && ! Schema::hasColumn('locations', 'postal_code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('postal_code', 6)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'postal_code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('postal_code');
            });
        }
        Schema::dropIfExists('post_offices');
    }
};
