<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KITOB VIDEOLARI — haftalik / oylik e'lon videolari uchun kitoblar to'plami.
 *
 * Tizim davr uchun kitoblarni avtomatik tanlaydi (eng ko'p sotilganlar),
 * admin tarkibini o'zgartiradi va shablonni tanlaydi. Video brauzerda
 * (canvas) yig'iladi — serverda faqat tanlov saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('book_video_sets')) {
            return;
        }

        Schema::create('book_video_sets', function (Blueprint $table) {
            $table->id();
            // weekly | monthly
            $table->string('period', 16);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('title', 120);
            // carousel | countdown | grid
            $table->string('template', 32)->default('carousel');
            // Tartib muhim: videoda aynan shu ketma-ketlikda chiqadi
            $table->json('book_ids');
            // Admin qo'lda o'zgartirganmi (avto tanlov ustidan yozilmasligi uchun)
            $table->boolean('is_customized')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['period', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_video_sets');
    }
};
