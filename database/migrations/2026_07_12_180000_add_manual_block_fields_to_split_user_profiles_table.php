<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('split_user_profiles')) {
            return;
        }

        Schema::table('split_user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('split_user_profiles', 'manual_blocked_at')) {
                $table->timestamp('manual_blocked_at')->nullable()->after('active_warning_count');
            }

            if (! Schema::hasColumn('split_user_profiles', 'manual_block_reason')) {
                $table->text('manual_block_reason')->nullable()->after('manual_blocked_at');
            }

            if (! Schema::hasColumn('split_user_profiles', 'manual_blocked_by_admin_id')) {
                $table->unsignedBigInteger('manual_blocked_by_admin_id')->nullable()->after('manual_block_reason');
                $table->index('manual_blocked_by_admin_id', 'split_profiles_manual_blocked_by_admin_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('split_user_profiles')) {
            return;
        }

        Schema::table('split_user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('split_user_profiles', 'manual_blocked_by_admin_id')) {
                $table->dropIndex('split_profiles_manual_blocked_by_admin_idx');
                $table->dropColumn('manual_blocked_by_admin_id');
            }

            if (Schema::hasColumn('split_user_profiles', 'manual_block_reason')) {
                $table->dropColumn('manual_block_reason');
            }

            if (Schema::hasColumn('split_user_profiles', 'manual_blocked_at')) {
                $table->dropColumn('manual_blocked_at');
            }
        });
    }
};
