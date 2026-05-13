<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hub_staff')) {
            return;
        }

        Schema::table('hub_staff', function (Blueprint $table) {
            if (!Schema::hasColumn('hub_staff', 'username')) {
                $table->string('username')->nullable()->unique()->after('staffable_id');
            }
            if (!Schema::hasColumn('hub_staff', 'full_name')) {
                $table->string('full_name')->nullable()->after('username');
            }
            if (!Schema::hasColumn('hub_staff', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('hub_staff', 'password')) {
                $table->string('password')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('hub_staff', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (!Schema::hasColumn('hub_staff', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('permissions');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hub_staff MODIFY staffable_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE hub_staff MODIFY staffable_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('hub_staff')) {
            return;
        }

        Schema::table('hub_staff', function (Blueprint $table) {
            foreach (['last_seen_at', 'remember_token', 'password', 'phone_number', 'full_name', 'username'] as $column) {
                if (Schema::hasColumn('hub_staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
