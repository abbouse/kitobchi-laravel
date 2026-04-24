<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_premium_subscriptions')) {
            return;
        }

        Schema::create('seller_premium_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('seller_id');
            $table->string('plan', 32);
            $table->unsignedInteger('duration_months')->default(1);
            $table->unsignedBigInteger('price_uzs')->default(0);
            $table->string('status', 32)->default('active');
            $table->boolean('auto_renew')->default(true);
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('stop_reason', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index('expires_at');
            $table->foreign('seller_id')
                ->references('id')
                ->on('sellers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_premium_subscriptions');
    }
};
