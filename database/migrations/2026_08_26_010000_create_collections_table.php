<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dasturiy SEO uchun: "kolleksiya" (mavzuiy to'plam) sahifalari — masalan
// "Eng yaxshi detektiv kitoblar", "Bolalar uchun kitoblar". Bular alohida
// mahsulot sahifasi bo'la olmaydigan, lekin qidiruv hajmi katta bo'lgan
// mavzu-so'rovlarni (masalan "psixologiya bo'yicha kitoblar") nishonga
// oladi. Har biri unikal muharrirlik matni (intro) + tanlangan
// mahsulotlar ro'yxatidan iborat — sof filtr-URL emas, shu sabab alohida
// jadval (collection_items) orqali aniq mahsulotlar bog'lanadi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('intro')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('type', 20)->default('book'); // book | stationery | mixed
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
