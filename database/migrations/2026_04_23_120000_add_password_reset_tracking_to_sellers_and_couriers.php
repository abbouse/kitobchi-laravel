<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sellers')) {
            Schema::table('sellers', function (Blueprint $table) {
                if (!Schema::hasColumn('sellers', 'password_reset_limit')) {
                    $table->integer('password_reset_limit')->default(3)->after('password');
                }

                if (!Schema::hasColumn('sellers', 'password_reset_limit_reset_at')) {
                    $table->timestamp('password_reset_limit_reset_at')->nullable()->after('password_reset_limit');
                }
            });
        }

        if (Schema::hasTable('couriers')) {
            Schema::table('couriers', function (Blueprint $table) {
                if (!Schema::hasColumn('couriers', 'password_reset_limit')) {
                    $table->integer('password_reset_limit')->default(3)->after('password');
                }

                if (!Schema::hasColumn('couriers', 'password_reset_limit_reset_at')) {
                    $table->timestamp('password_reset_limit_reset_at')->nullable()->after('password_reset_limit');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'password_reset_limit_reset_at')) {
                $table->dropColumn('password_reset_limit_reset_at');
            }
        });

        Schema::table('couriers', function (Blueprint $table) {
            if (Schema::hasColumn('couriers', 'password_reset_limit_reset_at')) {
                $table->dropColumn('password_reset_limit_reset_at');
            }

            if (Schema::hasColumn('couriers', 'password_reset_limit')) {
                $table->dropColumn('password_reset_limit');
            }
        });
    }
};
