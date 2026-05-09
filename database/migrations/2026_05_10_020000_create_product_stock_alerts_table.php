<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_type', 24)->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'product_id', 'product_type', 'variant_id'],
                'product_stock_alerts_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_alerts');
    }
};
