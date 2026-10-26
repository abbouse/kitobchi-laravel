<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bepul yetkazish soati: minimal buyurtma summasi (mahsulotlar summasi, so'm).
 * 0 yoki NULL — cheklov yo'q.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_settings') && ! Schema::hasColumn('project_settings', 'flash_delivery_min_amount')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->unsignedInteger('flash_delivery_min_amount')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'flash_delivery_min_amount')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->dropColumn('flash_delivery_min_amount');
            });
        }
    }
};
