<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuryer bir vaqtda ushlab tura oladigan faol buyurtmalar soni —
 * boshqaruv sozlamalaridan boshqariladi (avval kodda 3 edi).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_settings') && ! Schema::hasColumn('project_settings', 'courier_max_active_orders')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('courier_max_active_orders')->default(3);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'courier_max_active_orders')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->dropColumn('courier_max_active_orders');
            });
        }
    }
};
