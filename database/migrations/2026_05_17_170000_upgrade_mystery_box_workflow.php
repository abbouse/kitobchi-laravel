<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mystery_box_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('mystery_box_subscriptions', 'preferred_dispatch_type')) {
                $table->enum('preferred_dispatch_type', ['courier', 'postal', 'pickup'])
                    ->default('courier')
                    ->after('address');
            }

            if (!Schema::hasColumn('mystery_box_subscriptions', 'assignment_meta')) {
                $table->json('assignment_meta')
                    ->nullable()
                    ->after('cancelled_at');
            }
        });

        Schema::table('mystery_box_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('mystery_box_deliveries', 'dispatch_type')) {
                $table->enum('dispatch_type', ['courier', 'postal', 'pickup'])
                    ->default('courier')
                    ->after('month_number');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'selection_mode')) {
                $table->enum('selection_mode', ['auto', 'manual'])
                    ->default('auto')
                    ->after('book_ids');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'selection_meta')) {
                $table->json('selection_meta')
                    ->nullable()
                    ->after('tracking_note');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'planned_for_date')) {
                $table->date('planned_for_date')
                    ->nullable()
                    ->after('selection_meta');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'ready_at')) {
                $table->timestamp('ready_at')
                    ->nullable()
                    ->after('prepared_at');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'arrived_to_post_at')) {
                $table->timestamp('arrived_to_post_at')
                    ->nullable()
                    ->after('shipped_at');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')
                    ->nullable()
                    ->after('arrived_to_post_at');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'customer_received_at')) {
                $table->timestamp('customer_received_at')
                    ->nullable()
                    ->after('delivered_at');
            }

            if (!Schema::hasColumn('mystery_box_deliveries', 'cancelled_at')) {
                $table->timestamp('cancelled_at')
                    ->nullable()
                    ->after('customer_received_at');
            }
        });

        DB::statement("
            ALTER TABLE `mystery_box_deliveries`
            MODIFY `status` ENUM(
                'pending',
                'preparing',
                'ready_to_ship',
                'shipped',
                'arrived_to_post',
                'out_for_delivery',
                'delivered',
                'customer_received',
                'cancelled'
            ) COLLATE utf8mb4_unicode_ci DEFAULT 'pending'
        ");

        DB::statement("
            CREATE INDEX `idx_mystery_delivery_ops`
            ON `mystery_box_deliveries` (`status`, `dispatch_type`, `planned_for_date`)
        ");
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX `idx_mystery_delivery_ops` ON `mystery_box_deliveries`');
        } catch (\Throwable) {
            // no-op
        }

        DB::statement("
            ALTER TABLE `mystery_box_deliveries`
            MODIFY `status` ENUM(
                'pending',
                'preparing',
                'shipped',
                'delivered'
            ) COLLATE utf8mb4_unicode_ci DEFAULT 'pending'
        ");

        Schema::table('mystery_box_deliveries', function (Blueprint $table) {
            foreach ([
                'dispatch_type',
                'selection_mode',
                'selection_meta',
                'planned_for_date',
                'ready_at',
                'arrived_to_post_at',
                'out_for_delivery_at',
                'customer_received_at',
                'cancelled_at',
            ] as $column) {
                if (Schema::hasColumn('mystery_box_deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('mystery_box_subscriptions', function (Blueprint $table) {
            foreach (['preferred_dispatch_type', 'assignment_meta'] as $column) {
                if (Schema::hasColumn('mystery_box_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
