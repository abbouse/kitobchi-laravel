<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_refunds')) {
            return;
        }

        Schema::create('order_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('seller_order_id')->nullable()->index();
            $table->unsignedBigInteger('seller_order_item_id')->nullable()->index();
            $table->unsignedBigInteger('seller_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 32)->index();
            $table->string('provider', 32)->nullable();
            $table->unsignedInteger('card_refund_amount')->default(0);
            $table->unsignedInteger('cashback_restore_amount')->default(0);
            $table->unsignedInteger('gift_cert_restore_amount')->default(0);
            $table->unsignedInteger('delivery_refund_amount')->default(0);
            $table->unsignedInteger('packaging_refund_amount')->default(0);
            $table->unsignedInteger('total_customer_value')->default(0);
            $table->string('status', 32)->default('pending')->index();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('receiver_card_ref', 255)->nullable();
            $table->string('reason_code', 64)->nullable()->index();
            $table->text('reason_note_uz')->nullable();
            $table->text('reason_note_ru')->nullable();
            $table->text('reason_note_en')->nullable();
            $table->text('reason_note_ja')->nullable();
            $table->text('custom_reason_note')->nullable();
            $table->json('provider_payload')->nullable();
            $table->unsignedBigInteger('processed_by_seller_id')->nullable();
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
    }
};
