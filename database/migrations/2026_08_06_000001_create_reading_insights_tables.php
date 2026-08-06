<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Reading Intelligence" — kitob/kanstovar sahifasida "bu menga mosmi?"
 * kartochkasi uchun ikkita kesh jadvali.
 *
 * 1) book_reading_insights — MAHSULOT darajasida, foydalanuvchidan mustaqil
 *    kontent-asosli tahlil (qiyinlik, kayfiyat, kimlar uchun, sharhlar
 *    xulosasi). Bir marta AI orqali generatsiya qilinadi, mahsulot matni
 *    o'zgarmaguncha qayta so'ralmaydi (content_hash — ProductVectorService
 *    dagi vector_text_hash bilan bir xil naqsh).
 *
 * 2) user_book_match_reasons — FOYDALANUVCHI+MAHSULOT juftligi darajasida,
 *    shaxsiy "nega mos" jumlasi keshi. purchase_context_hash orqali
 *    foydalanuvchi xarid tarixi o'zgarganda avtomatik eskiradi (alohida
 *    invalidatsiya job kerak emas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_reading_insights', function (Blueprint $table) {
            $table->id();
            $table->string('product_type', 20);
            $table->unsignedBigInteger('product_id');
            $table->string('content_hash', 32)->nullable();

            // Taksonomiya asosidagi (erkin matn emas) maydonlar — til fayllari
            // orqali tarjima qilinadi, AI faqat kalitni tanlaydi.
            $table->string('difficulty', 20)->nullable(); // light | medium | deep
            $table->json('mood_tags')->nullable(); // ['warm','fast_paced', ...]

            // Erkin matnli, lokal bo'yicha keshlangan maydonlar:
            // {"uz": "...", "ru": "...", "en": "...", "ja": "..."}
            $table->json('audience_fit')->nullable();
            $table->json('audience_avoid')->nullable();
            $table->json('review_synthesis')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['product_type', 'product_id']);
        });

        Schema::create('user_book_match_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('product_type', 20);
            $table->unsignedBigInteger('product_id');
            $table->string('locale', 5)->default('uz');

            $table->float('match_score')->nullable();
            $table->text('reason_text')->nullable();
            $table->string('purchase_context_hash', 32)->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_type', 'product_id', 'locale'], 'user_book_match_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_book_match_reasons');
        Schema::dropIfExists('book_reading_insights');
    }
};
