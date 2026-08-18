<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MUHIM: piyolamarket.uz'dagi "Ma'lumotlarim" (profil ma'lumotlari) sahifasi
 * bilan funksional parallellik uchun — u yerda foydalanuvchi profilida
 * "Tug'ilgan sana" maydoni bor, lekin bizning `users` jadvalimizda bunday
 * ustun UMUMAN mavjud emas edi (haqiqiy schema dump — database/schema/
 * kitobchi_structure.sql — orqali tasdiqlangan: faqat `sex` va `email`
 * mavjud, `birthdate` yo'q). Shu sababli bu maydonni frontendda ko'rsatish/
 * saqlashdan oldin, avval haqiqiy DB ustunini qo'shish kerak edi.
 *
 * `email` ustuni ALLAQACHON mavjud (users jadvalida bor), shuning uchun bu
 * migratsiya faqat `birthdate`ni qo'shadi — email uchun faqat controller
 * (settings() metodi) va AuthController::successUserResponse() javobini
 * kengaytirish yetarli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('sex');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('birthdate');
        });
    }
};
