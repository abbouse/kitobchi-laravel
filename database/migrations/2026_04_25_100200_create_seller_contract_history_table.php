<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * seller_contract_history — seller shartnomasi bo'yicha audit log.
 *
 * Har safar shartnoma yaratilganda / uzaytirilganda / to'xtatilganda yangi qator
 * qo'shiladi. Admin paneldagi "Shartnoma tarixi" bo'limi shu jadvaldan o'qiydi.
 *
 * `action` — qanday harakat:
 *   - created:    birinchi marta shartnoma yaratildi
 *   - extended:   mavjud shartnoma uzaytirildi
 *   - renewed:    tugagan shartnoma qayta tuzildi
 *   - terminated: shartnoma to'xtatildi (sellerni olib tashlash oldidan)
 *   - updated:    boshqa o'zgarishlar (raqam, sana tuzatish, eslatmalar)
 *
 * `old_expires_at` / `new_expires_at` — kechagi va bugungi tugash sanasi (uzaytirishda).
 * `performed_by` — admin user id (users.id).
 * `notes` — admin qo'lda yozgan izoh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_contract_history', function (Blueprint $table) {
            $table->id();
            // sellers.id = bigint SIGNED — bigInteger (signed) ishlatamiz
            $table->bigInteger('seller_id');
            $table->enum('action', ['created', 'extended', 'renewed', 'terminated', 'updated'])
                  ->index();
            $table->string('contract_number', 50)->nullable();
            $table->date('old_expires_at')->nullable();
            $table->date('new_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // users.id
            $table->timestamp('created_at')->useCurrent();

            $table->index('seller_id');
            $table->index(['seller_id', 'created_at']);

            $table->foreign('seller_id')
                  ->references('id')->on('sellers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_contract_history');
    }
};
