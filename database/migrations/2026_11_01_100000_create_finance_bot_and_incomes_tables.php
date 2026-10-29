<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. platform_expenses ga Telegram va manba ustunlarini qo'shish
        Schema::table('platform_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('platform_expenses', 'telegram_user_id')) {
                $table->unsignedBigInteger('telegram_user_id')->nullable()->index()->after('created_by');
            }
            if (! Schema::hasColumn('platform_expenses', 'telegram_username')) {
                $table->string('telegram_username', 64)->nullable()->after('telegram_user_id');
            }
            if (! Schema::hasColumn('platform_expenses', 'telegram_name')) {
                $table->string('telegram_name', 255)->nullable()->after('telegram_username');
            }
            if (! Schema::hasColumn('platform_expenses', 'telegram_message_id')) {
                $table->unsignedBigInteger('telegram_message_id')->nullable()->after('telegram_name');
            }
            if (! Schema::hasColumn('platform_expenses', 'source')) {
                $table->string('source', 32)->default('panel')->after('telegram_message_id');
            }
        });

        // 2. platform_incomes — kirimlar, tushumlar va foydalar jadvali
        if (! Schema::hasTable('platform_incomes')) {
            Schema::create('platform_incomes', function (Blueprint $table) {
                $table->id();
                $table->string('type', 64)->default('other')->index();
                $table->unsignedBigInteger('amount');
                $table->date('received_at')->index();
                $table->string('title', 255);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('courier_id')->nullable()->index();
                $table->string('reference', 120)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('telegram_user_id')->nullable()->index();
                $table->string('telegram_username', 64)->nullable();
                $table->string('telegram_name', 255)->nullable();
                $table->unsignedBigInteger('telegram_message_id')->nullable();
                $table->string('source', 32)->default('telegram_bot');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_incomes');

        Schema::table('platform_expenses', function (Blueprint $table) {
            $cols = ['telegram_user_id', 'telegram_username', 'telegram_name', 'telegram_message_id', 'source'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('platform_expenses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

