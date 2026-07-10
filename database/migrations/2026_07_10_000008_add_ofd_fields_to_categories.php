<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategoriya darajasidagi OFD kodlari.
 *
 * IKPU aniqlash zanjiri: mahsulot kodi → kategoriya kodi → global default.
 * Shu tufayli har bir mahsulotga alohida kod kiritish shart emas —
 * kategoriyaga bir marta kiritiladi (ayniqsa kanselyariya uchun muhim,
 * chunki ruchka/daftar/sumka tasnifda alohida pozitsiyalar).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['book_categories', 'stationery_categories'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'ofd_ikpu_code')) {
                    $t->string('ofd_ikpu_code', 20)->nullable();
                }
                if (! Schema::hasColumn($table, 'ofd_package_code')) {
                    $t->string('ofd_package_code', 20)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['book_categories', 'stationery_categories'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['ofd_ikpu_code', 'ofd_package_code'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
