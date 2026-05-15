<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_cards')) {
            return;
        }

        Schema::table('user_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('user_cards', 'provider')) {
                $table->string('provider', 24)->default('paylov')->after('user_id');
            }
            if (!Schema::hasColumn('user_cards', 'provider_card_id')) {
                $table->string('provider_card_id')->nullable()->after('provider');
            }
            if (!Schema::hasColumn('user_cards', 'card_name')) {
                $table->string('card_name')->nullable()->after('provider_card_id');
            }
            if (!Schema::hasColumn('user_cards', 'expire_date')) {
                $table->string('expire_date', 4)->nullable()->after('card_number');
            }
            if (!Schema::hasColumn('user_cards', 'phone_number')) {
                $table->string('phone_number', 32)->nullable()->after('expire_date');
            }
            if (!Schema::hasColumn('user_cards', 'vendor')) {
                $table->string('vendor', 32)->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('user_cards', 'processing')) {
                $table->string('processing', 32)->nullable()->after('vendor');
            }
            if (!Schema::hasColumn('user_cards', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('user_cards', 'is_temporary')) {
                $table->boolean('is_temporary')->default(false)->after('is_default');
            }
            if (!Schema::hasColumn('user_cards', 'pending_order_id')) {
                $table->unsignedBigInteger('pending_order_id')->nullable()->after('is_temporary');
            }
            if (!Schema::hasColumn('user_cards', 'provider_meta')) {
                $table->json('provider_meta')->nullable()->after('pending_order_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_cards')) {
            return;
        }

        Schema::table('user_cards', function (Blueprint $table) {
            foreach ([
                'provider',
                'provider_card_id',
                'card_name',
                'expire_date',
                'phone_number',
                'vendor',
                'processing',
                'is_default',
                'is_temporary',
                'pending_order_id',
                'provider_meta',
            ] as $column) {
                if (Schema::hasColumn('user_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
