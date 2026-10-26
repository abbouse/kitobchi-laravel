<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kitobxon darajalariga qiziqarliroq nomlar va gerb ranglari.
 * Faqat boshqaruvda hali o'zgartirilmagan (standart nomli) darajalar yangilanadi.
 */
return new class extends Migration
{
    private const MAP = [
        'reader' => ['Kitobxon', ['Yangi sahifa', 'Новая страница', 'New Page', '新しいページ'], '#51CF66'],
        'active_reader' => ['Faol kitobxon', ['Sahifa sayyohi', 'Странник страниц', 'Page Wanderer', 'ページの旅人'], '#339AF0'],
        'book_lover' => ['Kitob muxlisi', ['Kitob ishqibozi', 'Книжный энтузиаст', 'Book Enthusiast', '本の熱狂者'], '#FF6B6B'],
        'bibliophile' => ['Bibliofil', ['Javon sohibi', 'Хранитель полки', 'Shelf Keeper', '本棚の守り手'], '#845EF7'],
        'literary_mentor' => ["Adabiy yo'lboshchi", ['Kutubxona sultoni', 'Султан библиотеки', 'Library Sultan', '図書館のスルタン'], '#F59F00'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('loyalty_levels')) {
            return;
        }
        foreach (self::MAP as $key => [$old, $names, $color]) {
            DB::table('loyalty_levels')->where('key', $key)->whereIn('name_uz', [$old, 'Yangi varaq'])->update([
                'name_uz' => $names[0], 'name_ru' => $names[1], 'name_en' => $names[2], 'name_ja' => $names[3],
                'color' => $color, 'updated_at' => now(),
            ]);
        }
        \Illuminate\Support\Facades\Cache::forget('loyalty_levels:all');
    }

    public function down(): void
    {
    }
};
