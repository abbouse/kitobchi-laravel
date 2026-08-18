<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE `bot_ticket_messages` MODIFY `message_type` VARCHAR(50) NOT NULL DEFAULT 'text'");
        } catch (\Throwable) {
            if (Schema::hasTable('bot_ticket_messages')) {
                Schema::table('bot_ticket_messages', function (Blueprint $table) {
                    $table->string('message_type', 50)->default('text')->change();
                });
            }
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `bot_ticket_messages` MODIFY `message_type` ENUM('text', 'photo', 'document', 'voice', 'video', 'system') NOT NULL DEFAULT 'text'");
        } catch (\Throwable) {}
    }
};
