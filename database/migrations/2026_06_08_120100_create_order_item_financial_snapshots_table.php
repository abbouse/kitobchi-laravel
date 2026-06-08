<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_item_financial_snapshots')) {
            return;
        }

        Schema::create('order_item_financial_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sold_id')->index();
            $table->unsignedBigInteger('seller_order_id')->nullable()->index();
            $table->unsignedBigInteger('seller_order_item_id')->nullable()->unique();
            $table->unsignedBigInteger('seller_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('product_type', 32)->nullable()->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('gross_amount')->default(0);
            $table->unsignedInteger('promo_allocated')->default(0);
            $table->unsignedInteger('cashback_allocated')->default(0);
            $table->unsignedInteger('gift_cert_allocated')->default(0);
            $table->unsignedInteger('card_paid_allocated')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_financial_snapshots');
    }
};
