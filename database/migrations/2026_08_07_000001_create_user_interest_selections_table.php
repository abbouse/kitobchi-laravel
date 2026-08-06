<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding'dagi "Sizga nima yoqadi?" chip-tanlash qadamida foydalanuvchi
 * belgilagan kitob kategoriyalari — Reading Intelligence uchun eng past
 * ishonchli signal (UserTasteProfileService::INTEREST_WEIGHT), sovuq-start
 * (hali xarid/savat/sevimlisi yo'q yangi mijoz) holatida did-vektorni
 * ishga tushirish uchun ishlatiladi.
 *
 * `has_selected_interests` — bu qadam foydalanuvchiga QAYTA
 * ko'rsatilmasligi uchun (tanlagan yoki "o'tkazib yuborgan" bo'lsa ham,
 * ikkalasi ham true qiladi — cheksiz qayta so'rash bezovta qiladi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interest_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_selected_interests')->default(false)->after('isVerified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interest_selections');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_selected_interests');
        });
    }
};
