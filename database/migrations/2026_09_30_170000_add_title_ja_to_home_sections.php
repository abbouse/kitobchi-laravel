<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bosh sahifa bo'limlari uchun yapon tilidagi sarlavha.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }
        if (! Schema::hasColumn('home_sections', 'title_ja')) {
            Schema::table('home_sections', function (Blueprint $table) {
                $table->string('title_ja')->nullable()->after('title_en');
            });
        }

        $defaults = [
            'genres' => 'ジャンル',
            'recently_viewed' => '最近見た本',
            'for_you' => 'あなたへのおすすめ',
            'bestsellers' => '今週のベストセラー',
            'center_banners' => 'バナー',
            'new_arrivals' => '新着',
            'coming_soon' => '近日発売',
            'discount_ending' => 'まもなく終了のセール',
            'collections' => '特集',
            'club_trending' => 'ブッククラブで話題',
            'shops' => '書店',
        ];
        foreach ($defaults as $key => $ja) {
            DB::table('home_sections')->where('key', $key)->whereNull('title_ja')->update(['title_ja' => $ja]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('home_sections', 'title_ja')) {
            Schema::table('home_sections', fn (Blueprint $table) => $table->dropColumn('title_ja'));
        }
    }
};
