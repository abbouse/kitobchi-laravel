<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_cards')) {
            if (Schema::hasColumn('user_cards', 'provider')) {
                DB::table('user_cards')
                    ->where(function ($query) {
                        $query->whereNull('provider')
                            ->orWhere('provider', 'payme');
                    })
                    ->update(['provider' => 'paylov']);

            }

            if (Schema::hasColumn('user_cards', 'payme_token') && !Schema::hasColumn('user_cards', 'token')) {
                DB::statement('ALTER TABLE `user_cards` CHANGE `payme_token` `token` TEXT NULL');
            } elseif (Schema::hasColumn('user_cards', 'payme_token')) {
                Schema::table('user_cards', function (Blueprint $table) {
                    $table->dropColumn('payme_token');
                });
            }
        }

        if (Schema::hasTable('transactions')) {
            if (Schema::hasColumn('transactions', 'provider')) {
                DB::table('transactions')
                    ->where(function ($query) {
                        $query->whereNull('provider')
                            ->orWhere('provider', 'payme');
                    })
                    ->update(['provider' => 'paylov']);

            }

            Schema::table('transactions', function (Blueprint $table) {
                foreach (['paycom_transaction_id', 'paycom_time', 'paycom_time_datetime'] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Legacy payme artefaktlarini tiklash qo'llab-quvvatlanmaydi.
    }
};
