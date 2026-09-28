<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hamkorlik arizasidagi qo'shimcha javoblar (muallif: kitob holati, nechta
 * kitob, nashr usuli...; do'kon: assortiment, offline do'kon...). Shartnoma
 * paytida menejer shu ma'lumotdan boshlaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sellers') && ! Schema::hasColumn('sellers', 'application_data')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->json('application_data')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sellers') && Schema::hasColumn('sellers', 'application_data')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->dropColumn('application_data');
            });
        }
    }
};
