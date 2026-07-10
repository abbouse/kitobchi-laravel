<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OFD fiskalizatsiya uchun mahsulot darajasidagi maydonlar:
 * - ofd_ikpu_code: mahsulotning IKPU klassifikatsiya kodi (17 xonali).
 *   Bo'lmasa config'dagi tur bo'yicha default ishlatiladi.
 * - ofd_package_code: qadoq turi kodi (tasnif.soliq.uz dan).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'stationeries'] as $table) {
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
        foreach (['books', 'stationeries'] as $table) {
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
