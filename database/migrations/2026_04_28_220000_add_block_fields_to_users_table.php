<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'blocked_until')) {
                $table->timestamp('blocked_until')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('blocked_until');
            }
            if (!Schema::hasColumn('users', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('blocked_at');
            }
            if (!Schema::hasColumn('users', 'blocked_by_admin_id')) {
                $table->unsignedBigInteger('blocked_by_admin_id')->nullable()->after('block_reason');
                $table->index('blocked_by_admin_id', 'users_blocked_by_admin_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'blocked_by_admin_id')) {
                try {
                    $table->dropIndex('users_blocked_by_admin_id_idx');
                } catch (\Throwable $e) {
                }
            }

            foreach (['blocked_by_admin_id', 'block_reason', 'blocked_at', 'blocked_until'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
