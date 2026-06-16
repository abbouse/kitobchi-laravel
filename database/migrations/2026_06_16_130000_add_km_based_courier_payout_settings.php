<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('project_settings', 'courier_base_fee')) {
                    $table->unsignedInteger('courier_base_fee')->default(3000);
                }
                if (! Schema::hasColumn('project_settings', 'courier_price_per_km')) {
                    $table->unsignedInteger('courier_price_per_km')->default(1500);
                }
                if (! Schema::hasColumn('project_settings', 'courier_min_fee')) {
                    $table->unsignedInteger('courier_min_fee')->default(5000);
                }
                if (! Schema::hasColumn('project_settings', 'courier_bonus_rules')) {
                    $table->json('courier_bonus_rules')->nullable();
                }
            });
        }

        if (Schema::hasTable('courier_tasks')) {
            Schema::table('courier_tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('courier_tasks', 'distance_km')) {
                    $table->decimal('distance_km', 8, 2)->nullable()->after('cash_collect_amount');
                }
                if (! Schema::hasColumn('courier_tasks', 'base_fee_amount')) {
                    $table->unsignedInteger('base_fee_amount')->default(0)->after('distance_km');
                }
                if (! Schema::hasColumn('courier_tasks', 'distance_fee_amount')) {
                    $table->unsignedInteger('distance_fee_amount')->default(0)->after('base_fee_amount');
                }
                if (! Schema::hasColumn('courier_tasks', 'bonus_amount')) {
                    $table->unsignedInteger('bonus_amount')->default(0)->after('distance_fee_amount');
                }
                if (! Schema::hasColumn('courier_tasks', 'payout_breakdown')) {
                    $table->json('payout_breakdown')->nullable()->after('bonus_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courier_tasks')) {
            Schema::table('courier_tasks', function (Blueprint $table) {
                foreach ([
                    'distance_km',
                    'base_fee_amount',
                    'distance_fee_amount',
                    'bonus_amount',
                    'payout_breakdown',
                ] as $column) {
                    if (Schema::hasColumn('courier_tasks', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                foreach ([
                    'courier_base_fee',
                    'courier_price_per_km',
                    'courier_min_fee',
                    'courier_bonus_rules',
                ] as $column) {
                    if (Schema::hasColumn('project_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
