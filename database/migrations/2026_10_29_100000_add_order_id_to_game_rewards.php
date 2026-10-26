<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Sovg'a qutisi" yutug'i qaysi buyurtmaga qo'shib yuborilishi. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_rewards') && ! Schema::hasColumn('game_rewards', 'order_id')) {
            Schema::table('game_rewards', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('game_rewards') && Schema::hasColumn('game_rewards', 'order_id')) {
            Schema::table('game_rewards', function (Blueprint $table) {
                $table->dropColumn('order_id');
            });
        }
    }
};
