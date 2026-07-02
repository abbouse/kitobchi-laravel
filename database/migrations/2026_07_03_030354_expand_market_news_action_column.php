<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('market_news')) {
            return;
        }

        DB::table('market_news')
            ->whereNull('action')
            ->orWhere('action', '')
            ->update(['action' => 'news']);

        DB::statement("
            ALTER TABLE `market_news`
            MODIFY `action` VARCHAR(32) NOT NULL DEFAULT 'news'
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('market_news')) {
            return;
        }

        DB::table('market_news')
            ->whereNotIn('action', ['news', 'to_shop', 'to_product'])
            ->update(['action' => 'news']);

        DB::statement("
            ALTER TABLE `market_news`
            MODIFY `action` ENUM('news','to_shop','to_product') NOT NULL DEFAULT 'news'
        ");
    }
};
