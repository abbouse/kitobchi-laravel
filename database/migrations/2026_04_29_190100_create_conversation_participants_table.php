<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversation_participants')) {
            return;
        }

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member');
            $table->timestamp('muted_until')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'conv_participants_unique');
            $table->index(['user_id', 'conversation_id'], 'conv_participants_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
