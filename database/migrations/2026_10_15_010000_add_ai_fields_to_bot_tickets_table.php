<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_tickets')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('bot_tickets', 'ai_enabled')) {
                    $table->boolean('ai_enabled')->default(true)->after('admin_id');
                }
                if (! Schema::hasColumn('bot_tickets', 'ai_summary')) {
                    $table->text('ai_summary')->nullable()->after('ai_enabled');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_tickets')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('bot_tickets', 'ai_summary')) {
                    $table->dropColumn('ai_summary');
                }
                if (Schema::hasColumn('bot_tickets', 'ai_enabled')) {
                    $table->dropColumn('ai_enabled');
                }
            });
        }
    }
};
