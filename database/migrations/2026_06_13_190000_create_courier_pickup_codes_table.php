<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_pickup_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('courier_id')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['seller_id', 'order_id', 'courier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_pickup_codes');
    }
};
