<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('transactions', 'provider')) {
                $table->string('provider', 24)->default('paylov')->after('owner_id');
            }
            if (!Schema::hasColumn('transactions', 'provider_transaction_id')) {
                $table->string('provider_transaction_id')->nullable()->after('provider');
            }
            if (!Schema::hasColumn('transactions', 'provider_card_id')) {
                $table->string('provider_card_id')->nullable()->after('provider_transaction_id');
            }
            if (!Schema::hasColumn('transactions', 'provider_response')) {
                $table->json('provider_response')->nullable()->after('provider_card_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            foreach ([
                'owner_id',
                'provider',
                'provider_transaction_id',
                'provider_card_id',
                'provider_response',
            ] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
