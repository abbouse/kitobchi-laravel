<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('market_news')) {
            return;
        }

        Schema::table('market_news', function (Blueprint $table) {
            if (! Schema::hasColumn('market_news', 'title_uz')) {
                $table->string('title_uz')->nullable()->after('title');
            }
            if (! Schema::hasColumn('market_news', 'title_ru')) {
                $table->string('title_ru')->nullable()->after('title_uz');
            }
            if (! Schema::hasColumn('market_news', 'title_en')) {
                $table->string('title_en')->nullable()->after('title_ru');
            }
            if (! Schema::hasColumn('market_news', 'title_ja')) {
                $table->string('title_ja')->nullable()->after('title_en');
            }
            if (! Schema::hasColumn('market_news', 'description_uz')) {
                $table->text('description_uz')->nullable()->after('description');
            }
            if (! Schema::hasColumn('market_news', 'description_ru')) {
                $table->text('description_ru')->nullable()->after('description_uz');
            }
            if (! Schema::hasColumn('market_news', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ru');
            }
            if (! Schema::hasColumn('market_news', 'description_ja')) {
                $table->text('description_ja')->nullable()->after('description_en');
            }
        });

        DB::table('market_news')
            ->whereNull('title_uz')
            ->update(['title_uz' => DB::raw('title')]);

        DB::table('market_news')
            ->whereNull('description_uz')
            ->update(['description_uz' => DB::raw('description')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('market_news')) {
            return;
        }

        Schema::table('market_news', function (Blueprint $table) {
            foreach ([
                'description_ja',
                'description_en',
                'description_ru',
                'description_uz',
                'title_ja',
                'title_en',
                'title_ru',
                'title_uz',
            ] as $column) {
                if (Schema::hasColumn('market_news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
