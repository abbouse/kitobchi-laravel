<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Do'kon va kuryerlarni cheklash: sabab, muddat (vaqtinchalik blok),
 * kim blokladi va to'lovlarni (pul yechishni) muzlatish.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MUHIM: sellers.status enum'ida 'blocked' yo'q edi ('banned' bor) —
        // admin do'konni bloklasa (yoki 3-ogohlantirishda avtomatik blok)
        // MySQL xato berib, blok umuman saqlanmasdi.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sellers MODIFY status ENUM('pending','approved','rejected','banned','blocked') NULL DEFAULT 'pending'");
            foreach (['seller_ban_logs', 'courier_ban_logs'] as $logTable) {
                if (Schema::hasTable($logTable)) {
                    DB::statement("ALTER TABLE {$logTable} MODIFY type ENUM('warning','unban','block') NOT NULL DEFAULT 'warning'");
                }
            }
        }

        foreach (['sellers', 'couriers'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'blocked_at')) {
                    $t->timestamp('blocked_at')->nullable();
                }
                if (! Schema::hasColumn($table, 'blocked_until')) {
                    $t->timestamp('blocked_until')->nullable()->index();
                }
                if (! Schema::hasColumn($table, 'block_reason')) {
                    $t->string('block_reason', 500)->nullable();
                }
                if (! Schema::hasColumn($table, 'blocked_by_admin_id')) {
                    $t->unsignedBigInteger('blocked_by_admin_id')->nullable();
                }
                if (! Schema::hasColumn($table, 'payouts_frozen_at')) {
                    $t->timestamp('payouts_frozen_at')->nullable();
                }
                if (! Schema::hasColumn($table, 'payout_freeze_reason')) {
                    $t->string('payout_freeze_reason', 500)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['sellers', 'couriers'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['blocked_at', 'blocked_until', 'block_reason', 'blocked_by_admin_id', 'payouts_frozen_at', 'payout_freeze_reason'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
