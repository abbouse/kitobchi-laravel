<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_settings') && Schema::hasColumn('project_settings', 'split_min_reputation_score')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->dropColumn('split_min_reputation_score');
            });
        }

        if (Schema::hasTable('split_user_profiles') && Schema::hasColumn('split_user_profiles', 'last_refreshed_at')) {
            DB::table('split_user_profiles')->update(['last_refreshed_at' => null]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_settings') && ! Schema::hasColumn('project_settings', 'split_min_reputation_score')) {
            Schema::table('project_settings', function (Blueprint $table) {
                $table->decimal('split_min_reputation_score', 5, 2)->default(78);
            });
        }
    }
};
