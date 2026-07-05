<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_webhooks')) {
            return;
        }

        Schema::create('api_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('url');
            $table->json('events');                 // obuna bo'lingan hodisalar (yoki ["*"])
            $table->string('secret', 64);           // HMAC imzo uchun
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_delivered_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index(['api_client_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_webhooks');
    }
};
