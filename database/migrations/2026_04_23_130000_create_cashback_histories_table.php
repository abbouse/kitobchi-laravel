<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sold_id')->nullable()->constrained('solds')->nullOnDelete();
            $table->string('action', 40);
            $table->integer('amount');
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['sold_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_histories');
    }
};
