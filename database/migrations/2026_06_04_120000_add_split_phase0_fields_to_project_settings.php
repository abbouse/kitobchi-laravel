<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_settings')) {
            return;
        }

        Schema::table('project_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('project_settings', 'split_enabled')) {
                $table->boolean('split_enabled')->default(false);
            }

            if (! Schema::hasColumn('project_settings', 'split_public_enabled')) {
                $table->boolean('split_public_enabled')->default(false);
            }

            if (! Schema::hasColumn('project_settings', 'split_upfront_percent')) {
                $table->unsignedTinyInteger('split_upfront_percent')->default(25);
            }

            if (! Schema::hasColumn('project_settings', 'split_term_days')) {
                $table->unsignedSmallInteger('split_term_days')->default(60);
            }

            if (! Schema::hasColumn('project_settings', 'split_global_min_order_sum')) {
                $table->unsignedBigInteger('split_global_min_order_sum')->default(100000);
            }

            if (! Schema::hasColumn('project_settings', 'split_global_max_order_sum')) {
                $table->unsignedBigInteger('split_global_max_order_sum')->default(2000000);
            }

            if (! Schema::hasColumn('project_settings', 'split_global_min_limit')) {
                $table->unsignedBigInteger('split_global_min_limit')->default(300000);
            }

            if (! Schema::hasColumn('project_settings', 'split_global_max_limit')) {
                $table->unsignedBigInteger('split_global_max_limit')->default(2000000);
            }

            if (! Schema::hasColumn('project_settings', 'split_min_completed_orders')) {
                $table->unsignedSmallInteger('split_min_completed_orders')->default(3);
            }

            if (! Schema::hasColumn('project_settings', 'split_min_account_age_days')) {
                $table->unsignedSmallInteger('split_min_account_age_days')->default(90);
            }

            if (! Schema::hasColumn('project_settings', 'split_min_card_age_days')) {
                $table->unsignedSmallInteger('split_min_card_age_days')->default(45);
            }

            if (! Schema::hasColumn('project_settings', 'split_min_reputation_score')) {
                $table->decimal('split_min_reputation_score', 5, 2)->default(78);
            }

            if (! Schema::hasColumn('project_settings', 'split_max_active_contracts')) {
                $table->unsignedTinyInteger('split_max_active_contracts')->default(1);
            }

            if (! Schema::hasColumn('project_settings', 'split_default_fee_percent')) {
                $table->decimal('split_default_fee_percent', 5, 2)->default(0);
            }

            if (! Schema::hasColumn('project_settings', 'split_card_delete_lock_enabled')) {
                $table->boolean('split_card_delete_lock_enabled')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_settings')) {
            return;
        }

        $columns = [
            'split_enabled',
            'split_public_enabled',
            'split_upfront_percent',
            'split_term_days',
            'split_global_min_order_sum',
            'split_global_max_order_sum',
            'split_global_min_limit',
            'split_global_max_limit',
            'split_min_completed_orders',
            'split_min_account_age_days',
            'split_min_card_age_days',
            'split_min_reputation_score',
            'split_max_active_contracts',
            'split_default_fee_percent',
            'split_card_delete_lock_enabled',
        ];

        Schema::table('project_settings', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('project_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
