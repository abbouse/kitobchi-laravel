<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Omadli maydon:
 *  - yutilgan kitob endi promokod emas — savatdagi qatorga bog'lanadi va user uchun
 *    1000 so'm turadi (do'konga to'liq narx, farqini platforma to'laydi);
 *  - barcha promokodlar va yutilgan kitob 15 kun amal qiladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('my_carts') && ! Schema::hasColumn('my_carts', 'game_reward_id')) {
            Schema::table('my_carts', function (Blueprint $table) {
                $table->unsignedBigInteger('game_reward_id')->nullable()->index();
            });
        }
        if (Schema::hasTable('game_prizes')) {
            DB::table('game_prizes')->update(['valid_days' => 15]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('my_carts') && Schema::hasColumn('my_carts', 'game_reward_id')) {
            Schema::table('my_carts', function (Blueprint $table) {
                $table->dropColumn('game_reward_id');
            });
        }
    }
};
