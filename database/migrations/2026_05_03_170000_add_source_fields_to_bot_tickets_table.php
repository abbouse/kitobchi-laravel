<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_tickets', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('user_id');
            $table->unsignedBigInteger('source_conversation_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_conversation_id'], 'bot_tickets_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bot_tickets', function (Blueprint $table) {
            $table->dropIndex('bot_tickets_source_idx');
            $table->dropColumn(['source_type', 'source_conversation_id']);
        });
    }
};
