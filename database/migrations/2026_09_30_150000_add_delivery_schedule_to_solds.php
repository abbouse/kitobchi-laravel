<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mijoz tanlagan yetkazish kuni va vaqt oralig'i (kuryer orqali).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('solds')) {
            return;
        }
        Schema::table('solds', function (Blueprint $table) {
            if (! Schema::hasColumn('solds', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->index();
            }
            if (! Schema::hasColumn('solds', 'delivery_slot')) {
                $table->string('delivery_slot', 8)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('solds')) {
            return;
        }
        Schema::table('solds', function (Blueprint $table) {
            foreach (['delivery_date', 'delivery_slot'] as $column) {
                if (Schema::hasColumn('solds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
