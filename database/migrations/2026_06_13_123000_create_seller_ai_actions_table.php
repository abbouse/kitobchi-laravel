<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_ai_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('requested_by_seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $table->string('action_type')->index();
            $table->enum('status', ['preview', 'applied', 'rolled_back', 'cancelled', 'failed'])->default('preview')->index();
            $table->string('source_file_name')->nullable();
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'action_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_ai_actions');
    }
};
