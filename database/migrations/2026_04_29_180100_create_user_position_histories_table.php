<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_position_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('from_position', 100)->nullable();
            $table->string('to_position', 100);
            $table->json('metrics_snapshot')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_position_histories');
    }
};
