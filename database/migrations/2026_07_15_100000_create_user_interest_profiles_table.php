<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_interest_profiles')) {
            return;
        }

        Schema::create('user_interest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('profile')->nullable();
            $table->json('signals')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['confidence_score', 'generated_at'], 'uip_confidence_generated_idx');
            $table->index('last_viewed_at', 'uip_last_viewed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interest_profiles');
    }
};
