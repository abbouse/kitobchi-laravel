<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('position_earned_at')->nullable()->after('position');
        });

        DB::table('users')
            ->whereNull('position')
            ->update(['position' => 'reader']);

        DB::table('users')
            ->whereNull('position_earned_at')
            ->update(['position_earned_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position_earned_at');
        });
    }
};
