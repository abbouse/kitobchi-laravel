<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_client_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_client_id')->index();
            $table->string('method', 12);
            $table->string('path', 255);
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_request_logs');
    }
};
