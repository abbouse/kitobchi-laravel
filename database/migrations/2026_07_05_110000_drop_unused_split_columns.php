<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split v2 dan keyin ishlatilmay qolgan ustunlarni tozalash:
     * - shartnomalar soni cheklovi olib tashlandi (limit yetguncha olaveradi)
     * - upfront/term/summa chegaralari va ustama endi tarif (split_plans) darajasida
     * - kategoriya darajasida faqat ruxsat/taqiq qoldi
     */
    public function up(): void
    {
        if (Schema::hasTable('project_settings')) {
            $columns = array_values(array_filter([
                'split_max_active_contracts',
                'split_upfront_percent',
                'split_term_days',
                'split_global_min_order_sum',
                'split_global_max_order_sum',
                'split_default_fee_percent',
            ], fn (string $column) => Schema::hasColumn('project_settings', $column)));

            if ($columns !== []) {
                Schema::table('project_settings', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }

        if (Schema::hasTable('split_user_profiles') && Schema::hasColumn('split_user_profiles', 'max_active_contracts')) {
            Schema::table('split_user_profiles', fn (Blueprint $table) => $table->dropColumn('max_active_contracts'));
        }

        if (Schema::hasTable('split_category_rules')) {
            $columns = array_values(array_filter([
                'fee_percent',
                'min_order_sum_override',
                'max_order_sum_override',
                'upfront_percent_override',
            ], fn (string $column) => Schema::hasColumn('split_category_rules', $column)));

            if ($columns !== []) {
                Schema::table('split_category_rules', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings')) {
            Schema::table('project_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('project_settings', 'split_max_active_contracts')) {
                    $table->unsignedTinyInteger('split_max_active_contracts')->default(1);
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
                if (! Schema::hasColumn('project_settings', 'split_default_fee_percent')) {
                    $table->decimal('split_default_fee_percent', 5, 2)->default(0);
                }
            });
        }

        if (Schema::hasTable('split_user_profiles') && ! Schema::hasColumn('split_user_profiles', 'max_active_contracts')) {
            Schema::table('split_user_profiles', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_active_contracts')->default(0);
            });
        }

        if (Schema::hasTable('split_category_rules')) {
            Schema::table('split_category_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('split_category_rules', 'fee_percent')) {
                    $table->decimal('fee_percent', 5, 2)->nullable();
                }
                if (! Schema::hasColumn('split_category_rules', 'min_order_sum_override')) {
                    $table->unsignedBigInteger('min_order_sum_override')->nullable();
                }
                if (! Schema::hasColumn('split_category_rules', 'max_order_sum_override')) {
                    $table->unsignedBigInteger('max_order_sum_override')->nullable();
                }
                if (! Schema::hasColumn('split_category_rules', 'upfront_percent_override')) {
                    $table->unsignedTinyInteger('upfront_percent_override')->nullable();
                }
            });
        }
    }
};
