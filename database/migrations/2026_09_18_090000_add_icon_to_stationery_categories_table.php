<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategoriya rasmi.
 *
 * `book_categories` da `icon` ustuni allaqachon bor edi, lekin boshqaruv
 * panelida u oddiy matn maydoni bo'lgani uchun u yerga rasm yo'li emas,
 * emoji yoki bo'sh qiymat tushardi — natijada bosh sahifadagi kataloglar
 * doim umumiy logotip bilan ko'rinardi. Endi ikkala kategoriya turi ham
 * rasm saqlaydi, shuning uchun kanselyariya kategoriyalariga ham shu
 * ustunni qo'shamiz.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stationery_categories') && ! Schema::hasColumn('stationery_categories', 'icon')) {
            Schema::table('stationery_categories', function (Blueprint $table) {
                $table->string('icon', 255)->nullable()->after('slug');
            });
        }

        if (Schema::hasTable('book_categories') && ! Schema::hasColumn('book_categories', 'icon')) {
            Schema::table('book_categories', function (Blueprint $table) {
                $table->string('icon', 255)->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stationery_categories') && Schema::hasColumn('stationery_categories', 'icon')) {
            Schema::table('stationery_categories', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
