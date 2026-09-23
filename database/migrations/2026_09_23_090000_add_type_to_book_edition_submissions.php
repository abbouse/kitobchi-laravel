<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arizalar navbati endi ikki xil bo'ladi:
 *   new_book   — katalogda yo'q kitobni qo'shish (old/orqa muqova majburiy);
 *   correction — mavjud kartadagi xatoni tuzatish taklifi (do'kon kartani
 *                o'zi tahrirlay olmaydi, faqat taklif yuboradi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_edition_submissions', function (Blueprint $table) {
            $table->string('type', 16)->default('new_book')->after('seller_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('book_edition_submissions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
