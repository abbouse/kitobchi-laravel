<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kitobchi ID kirish kodlarini Kitobchi ilovasidagi rasmiy chatga yetkazish.
 * `messages` jadvalida faqat yashirilgan matn bo'ladi; kodning o'zi shu yerda
 * APP_KEY bilan shifrlangan holda, faqat amal qilish muddatigacha saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_code_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable()->unique();
            $table->string('client_name', 120);
            $table->text('code_enc')->nullable(); // muddati tugagach/ishlatilgach null qilinadi
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_code_deliveries');
    }
};
