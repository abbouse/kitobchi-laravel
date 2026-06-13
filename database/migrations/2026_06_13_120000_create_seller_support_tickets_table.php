<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->enum('status', ['open', 'answered', 'waiting', 'closed'])->default('open')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('seller_unread_count')->default(0);
            $table->unsignedInteger('admin_unread_count')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->text('close_reason')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });

        Schema::create('seller_support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('seller_support_tickets')->cascadeOnDelete();
            $table->enum('sender_type', ['seller', 'admin', 'system'])->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_support_ticket_messages');
        Schema::dropIfExists('seller_support_tickets');
    }
};
