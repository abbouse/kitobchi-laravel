<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (! Schema::hasColumn('sellers', 'can_withdraw_balance')) {
                $table->boolean('can_withdraw_balance')
                    ->default(false)
                    ->after('staff_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasColumn('sellers', 'can_withdraw_balance')) {
                $table->dropColumn('can_withdraw_balance');
            }
        });
    }
};
