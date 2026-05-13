<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            if (!Schema::hasColumn('couriers', 'cod_reserved_amount')) {
                $table->unsignedInteger('cod_reserved_amount')->default(0)->after('balance');
            }
        });

        Schema::table('courier_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('courier_tasks', 'is_cod')) {
                $table->boolean('is_cod')->default(false)->after('status_code');
            }
            if (!Schema::hasColumn('courier_tasks', 'cash_collect_amount')) {
                $table->unsignedInteger('cash_collect_amount')->default(0)->after('is_cod');
            }
            if (!Schema::hasColumn('courier_tasks', 'cod_reserved_at')) {
                $table->timestamp('cod_reserved_at')->nullable()->after('cash_collect_amount');
            }
            if (!Schema::hasColumn('courier_tasks', 'cod_released_at')) {
                $table->timestamp('cod_released_at')->nullable()->after('cod_reserved_at');
            }
            if (!Schema::hasColumn('courier_tasks', 'wallet_debited_at')) {
                $table->timestamp('wallet_debited_at')->nullable()->after('cod_released_at');
            }
            if (!Schema::hasColumn('courier_tasks', 'cash_reconciled_at')) {
                $table->timestamp('cash_reconciled_at')->nullable()->after('wallet_debited_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courier_tasks', function (Blueprint $table) {
            foreach ([
                'cash_reconciled_at',
                'wallet_debited_at',
                'cod_released_at',
                'cod_reserved_at',
                'cash_collect_amount',
                'is_cod',
            ] as $column) {
                if (Schema::hasColumn('courier_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('couriers', function (Blueprint $table) {
            if (Schema::hasColumn('couriers', 'cod_reserved_amount')) {
                $table->dropColumn('cod_reserved_amount');
            }
        });
    }
};
