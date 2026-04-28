<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * books jadvaliga ISBN ustuni — barcode scanner orqali avto-fill va
 * "do'kon ichida" mijoz xaridi uchun mahsulotni topish kaliti.
 *
 *  - varchar(20) — ISBN-10 (10) va ISBN-13 (13) ham, "-" ishlatilgan format
 *    ham (eg. 978-9943-...) sig'adi.
 *  - nullable — eski kitoblar va ISBN'siz manbalar (qo'lyozma va h.k.) uchun.
 *  - INDEX — by-isbn qidiruv tezligini ta'minlash uchun. UNIQUE EMAS:
 *    bir xil kitobni boshqa sellerlar ham qo'shadi (ular bo'yicha qidirish
 *    avto-fill uchun maxsus ishlaydi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'isbn')) {
                $table->string('isbn', 20)->nullable()->after('author');
                $table->index('isbn', 'books_isbn_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            try { $table->dropIndex('books_isbn_index'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('books', 'isbn')) {
                $table->dropColumn('isbn');
            }
        });
    }
};
