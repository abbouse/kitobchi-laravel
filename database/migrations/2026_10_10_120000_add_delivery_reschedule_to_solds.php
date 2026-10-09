<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kechikkan yetkazishlar avtomatik keyingi kunga ko'chiriladi — tarix saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('solds')) {
            return;
        }

        Schema::table('solds', function (Blueprint $table) {
            if (! Schema::hasColumn('solds', 'delivery_original_date')) {
                $table->date('delivery_original_date')->nullable();
            }
            if (! Schema::hasColumn('solds', 'delivery_rescheduled_count')) {
                $table->unsignedSmallInteger('delivery_rescheduled_count')->default(0);
            }
            if (! Schema::hasColumn('solds', 'delivery_rescheduled_at')) {
                $table->timestamp('delivery_rescheduled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('solds')) {
            return;
        }

        Schema::table('solds', function (Blueprint $table) {
            foreach (['delivery_original_date', 'delivery_rescheduled_count', 'delivery_rescheduled_at'] as $column) {
                if (Schema::hasColumn('solds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
