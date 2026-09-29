<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bosh sahifa bo'limlari — tartibi, yoqilgani va sarlavhalari boshqaruvdan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            Schema::create('home_sections', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('type', 32);
                $table->string('title_uz')->nullable();
                $table->string('title_ru')->nullable();
                $table->string('title_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(0);
                $table->unsignedTinyInteger('item_limit')->default(12);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        $defaults = [
            ['genres', 'genres', 'Janrlar', 'Жанры', 'Genres', 10],
            ['recently_viewed', 'recently_viewed', "Oxirgi ko'rganlaringiz", 'Вы смотрели', 'Recently viewed', 15],
            ['for_you', 'for_you', 'Siz uchun', 'Для вас', 'For you', 20],
            ['bestsellers', 'bestsellers', "Haftaning ko'p sotilgani", 'Бестселлеры недели', 'Bestsellers of the week', 30],
            ['center_banners', 'center_banners', 'Bannerlar', 'Баннеры', 'Banners', 40],
            ['new_arrivals', 'new_arrivals', 'Yangi kitoblar', 'Новинки', 'New arrivals', 50],
            ['coming_soon', 'coming_soon', 'Tez orada', 'Скоро в продаже', 'Coming soon', 60],
            ['discount_ending', 'discount_ending', 'Chegirma tugayapti', 'Скидка заканчивается', 'Deals ending soon', 70],
            ['collections', 'collections', "To'plamlar", 'Подборки', 'Collections', 80],
            ['club_trending', 'club_trending', 'Klubda muhokama qilinmoqda', 'Обсуждают в клубе', 'Trending in the club', 90],
            ['shops', 'shops', "Do'konlar", 'Магазины', 'Shops', 1000],
        ];
        foreach ($defaults as [$key, $type, $uz, $ru, $en, $pos]) {
            if (DB::table('home_sections')->where('key', $key)->exists()) {
                continue;
            }
            DB::table('home_sections')->insert([
                'key' => $key,
                'type' => $type,
                'title_uz' => $uz,
                'title_ru' => $ru,
                'title_en' => $en,
                'is_active' => true,
                'position' => $pos,
                'item_limit' => 12,
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
