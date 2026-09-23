<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TAKLIF (books) JADVALIDAN KERAKSIZ USTUNLARNI OLIB TASHLASH.
 *
 * `condition` — biz faqat YANGI kitob sotamiz. Ustun hech qayerda (savat,
 * buyurtma, qidiruv, fiskal, hamkor API) o'qilmasdi: faqat taklif qo'shish
 * formasi va admin ro'yxatida ko'rsatilardi. API javobida `condition` maydoni
 * shartnoma buzilmasligi uchun `'new'` literal sifatida qoladi
 * (ProductPayloadFormatter).
 *
 * Takroriy indeks ham yo'q edi — faqat ustunning o'zi tushadi.
 *
 * ESLATMA: kartadagi ma'lumotni takrorlaydigan 14 ta ustun (name, author,
 * isbn, category_id, description, images, ...) BU YERDA TEGILMAYDI. Ular hali
 * ham FULLTEXT qidiruv, hamkor API (`scopeWhereIsbn`) va `edition_id IS NULL`
 * bo'lgan qatorlar uchun yagona manba — sabablari `docs/katalog-books-vs-book-editions.md`
 * da yozilgan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('books', 'condition')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('condition');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('books', 'condition')) {
            Schema::table('books', function (Blueprint $table) {
                $table->string('condition', 16)->default('new');
            });
        }
    }
};
