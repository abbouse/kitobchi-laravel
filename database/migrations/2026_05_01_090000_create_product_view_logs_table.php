<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_view_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_type', 24)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('recommendation_active')->default(false)->index();
            $table->string('device_id', 128)->nullable()->index();
            $table->string('session_id', 128)->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'created_at'], 'pvl_seller_created_idx');
            $table->index(
                ['seller_id', 'recommendation_active', 'created_at'],
                'pvl_seller_rec_created_idx'
            );
            $table->index(['product_type', 'product_id', 'created_at'], 'pvl_product_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_view_logs');
    }
};
