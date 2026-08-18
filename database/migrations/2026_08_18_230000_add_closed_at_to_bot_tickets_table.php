<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_tickets') && !Schema::hasColumn('bot_tickets', 'closed_at')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('close_reason');
                $table->index('closed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bot_tickets') && Schema::hasColumn('bot_tickets', 'closed_at')) {
            Schema::table('bot_tickets', function (Blueprint $table) {
                $table->dropIndex(['closed_at']);
                $table->dropColumn('closed_at');
            });
        }
    }
};
