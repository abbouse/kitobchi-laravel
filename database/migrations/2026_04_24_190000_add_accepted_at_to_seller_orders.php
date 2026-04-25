<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * seller_orders jadvaliga `accepted_at` ustunini qo'shish.
 *
 * Bu ustun — seller buyurtmani qabul qilgan (status 1 → 2 o'tgan) aniq
 * vaqtni yozib qo'yish uchun. Shuning orqali seller.response_time_hours
 * (buyurtma yaratilgandan seller qabul qilgunicha o'rtacha vaqt) hisoblanadi.
 *
 * Migration xavfsiz: faqat nullable ustun qo'shadi, eski yozuvlarga ta'sir
 * qilmaydi. Eski (accepted_at = null) buyurtmalar o'rtacha hisobidan
 * chiqarib tashlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_orders', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->index('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('seller_orders', function (Blueprint $table) {
            $table->dropIndex(['accepted_at']);
            $table->dropColumn('accepted_at');
        });
    }
};
