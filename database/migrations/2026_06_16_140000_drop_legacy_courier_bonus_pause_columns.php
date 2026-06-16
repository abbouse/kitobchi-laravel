<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courier_orders')) {
            try {
                DB::statement('ALTER TABLE courier_orders DROP INDEX idx_courier_orders_status_sla');
            } catch (Throwable) {
                // Index may not exist on older databases.
            }

            Schema::table('courier_orders', function (Blueprint $table) {
                foreach ([
                    'pickup_bonus',
                    'locked_bonus',
                    'final_bonus',
                    'sla_deadline',
                    'is_customer_delay',
                    'customer_delay_started_at',
                    'customer_delay_count',
                    'total_delay_seconds',
                    'bonus_threshold_notified',
                    'sla_warning_notified',
                ] as $column) {
                    if (Schema::hasColumn('courier_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                foreach ([
                    'courier_surge_step',
                    'courier_surge_max',
                    'courier_surge_threshold',
                    'courier_sla_minutes',
                    'courier_penalty_step',
                ] as $column) {
                    if (Schema::hasColumn('project_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('project_settings', 'courier_surge_step')) {
                    $table->unsignedInteger('courier_surge_step')->default(500);
                }
                if (! Schema::hasColumn('project_settings', 'courier_surge_max')) {
                    $table->unsignedInteger('courier_surge_max')->default(10000);
                }
                if (! Schema::hasColumn('project_settings', 'courier_surge_threshold')) {
                    $table->unsignedInteger('courier_surge_threshold')->default(5000);
                }
                if (! Schema::hasColumn('project_settings', 'courier_sla_minutes')) {
                    $table->unsignedSmallInteger('courier_sla_minutes')->default(45);
                }
                if (! Schema::hasColumn('project_settings', 'courier_penalty_step')) {
                    $table->unsignedInteger('courier_penalty_step')->default(300);
                }
            });
        }

        if (Schema::hasTable('courier_orders')) {
            Schema::table('courier_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('courier_orders', 'pickup_bonus')) {
                    $table->unsignedInteger('pickup_bonus')->default(0)->after('courierBonus');
                }
                if (! Schema::hasColumn('courier_orders', 'locked_bonus')) {
                    $table->unsignedInteger('locked_bonus')->nullable()->after('pickup_bonus');
                }
                if (! Schema::hasColumn('courier_orders', 'final_bonus')) {
                    $table->unsignedInteger('final_bonus')->nullable()->after('locked_bonus');
                }
                if (! Schema::hasColumn('courier_orders', 'sla_deadline')) {
                    $table->timestamp('sla_deadline')->nullable()->after('picked_up_at');
                }
                if (! Schema::hasColumn('courier_orders', 'is_customer_delay')) {
                    $table->boolean('is_customer_delay')->default(false)->after('sla_deadline');
                }
                if (! Schema::hasColumn('courier_orders', 'customer_delay_started_at')) {
                    $table->timestamp('customer_delay_started_at')->nullable()->after('is_customer_delay');
                }
                if (! Schema::hasColumn('courier_orders', 'customer_delay_count')) {
                    $table->unsignedTinyInteger('customer_delay_count')->default(0)->after('customer_delay_started_at');
                }
                if (! Schema::hasColumn('courier_orders', 'total_delay_seconds')) {
                    $table->unsignedInteger('total_delay_seconds')->default(0)->after('customer_delay_count');
                }
                if (! Schema::hasColumn('courier_orders', 'bonus_threshold_notified')) {
                    $table->boolean('bonus_threshold_notified')->default(false)->after('total_delay_seconds');
                }
                if (! Schema::hasColumn('courier_orders', 'sla_warning_notified')) {
                    $table->boolean('sla_warning_notified')->default(false)->after('bonus_threshold_notified');
                }
            });
        }
    }
};
