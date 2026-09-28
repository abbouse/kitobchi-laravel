<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OLDINDAN BUYURTMA (predzakaz).
 *
 * Hali chiqmagan, nashr etilayotgan kitobni do'kon oldindan sotuvga qo'yadi:
 * narxni o'zi belgilaydi, qoldiq = qabul qiladigan nusxalar soni, va
 * `preorder_release_date` — kitob jo'natila boshlanadigan kun. Sana o'tgach
 * taklif oddiy savdoga o'tadi (ustun tozalanmaydi — tarix uchun qoladi).
 *
 * Buyurtma qatoriga ham sana yoziladi: mijoz va do'kon buyurtma aynan
 * oldindan buyurtma ekanini va qachon jo'natilishini ko'radi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('books', 'preorder_release_date')) {
            Schema::table('books', function (Blueprint $table) {
                $table->date('preorder_release_date')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('seller_order_items', 'preorder_release_date')) {
            Schema::table('seller_order_items', function (Blueprint $table) {
                $table->date('preorder_release_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('seller_order_items', 'preorder_release_date')) {
            Schema::table('seller_order_items', function (Blueprint $table) {
                $table->dropColumn('preorder_release_date');
            });
        }

        if (Schema::hasColumn('books', 'preorder_release_date')) {
            Schema::table('books', function (Blueprint $table) {
                $table->dropIndex(['preorder_release_date']);
                $table->dropColumn('preorder_release_date');
            });
        }
    }
};
