<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('courier_id')->nullable()->after('shop_id');
            $table->unsignedBigInteger('order_id')->nullable()->after('courier_id');
            $table->index('courier_id');
            $table->index('order_id');
            $table->index(['type', 'courier_id']);
        });

        DB::statement("
            ALTER TABLE `conversations`
            MODIFY COLUMN `type` ENUM('personal','shop','group','courier')
            NOT NULL DEFAULT 'personal'
        ");
    }

    public function down(): void
    {
        DB::table('conversations')->where('type', 'courier')->update(['type' => 'personal']);

        DB::statement("
            ALTER TABLE `conversations`
            MODIFY COLUMN `type` ENUM('personal','shop','group')
            NOT NULL DEFAULT 'personal'
        ");

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['type', 'courier_id']);
            $table->dropIndex(['courier_id']);
            $table->dropIndex(['order_id']);
            $table->dropColumn(['courier_id', 'order_id']);
        });
    }
};
