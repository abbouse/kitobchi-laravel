<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sent_by', ['user', 'operator', 'admin', 'system'])->index();
            $table->unsignedBigInteger('operator_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->unsignedBigInteger('telegram_actor_id')->nullable()->index();
            $table->enum('message_type', ['text', 'photo', 'document', 'voice', 'video', 'system'])->default('text')->index();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->boolean('is_delivered')->default(true);
            $table->text('delivery_error')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('bot_tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_ticket_messages');
    }
};
