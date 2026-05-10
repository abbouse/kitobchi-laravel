<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_prompts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sold_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_type', 32);
            $table->string('product_name')->nullable();
            $table->timestamp('first_due_at');
            $table->timestamp('second_due_at')->nullable();
            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('second_sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason', 64)->nullable();
            $table->timestamps();

            $table->unique(
                ['sold_id', 'user_id', 'product_id', 'product_type'],
                'prp_order_user_product_unique'
            );
            $table->index(['user_id', 'closed_at']);
            $table->index(['product_type', 'product_id']);
            $table->index(['first_due_at', 'first_sent_at']);
            $table->index(['second_due_at', 'second_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_prompts');
    }
};
