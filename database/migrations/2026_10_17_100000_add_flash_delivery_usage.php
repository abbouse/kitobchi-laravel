<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bepul yetkazish soati: qaysi buyurtmada qo'llangani va har mijozga limit turi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solds')) {
            Schema::table('solds', function (Blueprint $table) {
                if (! Schema::hasColumn('solds', 'flash_free_delivery')) {
                    $table->boolean('flash_free_delivery')->default(false)->index();
                }
                if (! Schema::hasColumn('solds', 'flash_delivery_saved')) {
                    $table->unsignedInteger('flash_delivery_saved')->default(0);
                }
            });
        }

        // Promokod turi: bepul yetkazish (avval enum'da yo'q edi — bunday promokod saqlanmasdi)
        if (Schema::hasTable('promocodes') && \Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE promocodes MODIFY `type` ENUM('percent','uzs','free_item','free_delivery') NULL");
        }

        if (Schema::hasTable('project_settings') && ! Schema::hasColumn('project_settings', 'flash_delivery_limit')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->string('flash_delivery_limit', 10)->default('daily');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('solds')) {
            Schema::table('solds', function (Blueprint $table) {
                foreach (['flash_free_delivery', 'flash_delivery_saved'] as $column) {
                    if (Schema::hasColumn('solds', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'flash_delivery_limit')) {
            Schema::table('project_settings', fn (Blueprint $table) => $table->dropColumn('flash_delivery_limit'));
        }
    }
};
