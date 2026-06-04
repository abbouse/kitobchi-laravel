<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('split_category_rules')) {
            return;
        }

        Schema::create('split_category_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category_type', 32);
            $table->unsignedBigInteger('category_id');
            $table->boolean('enabled')->default(false);
            $table->decimal('fee_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('min_order_sum_override')->nullable();
            $table->unsignedBigInteger('max_order_sum_override')->nullable();
            $table->unsignedTinyInteger('upfront_percent_override')->nullable();
            $table->timestamps();

            $table->unique(['category_type', 'category_id']);
            $table->index(['category_type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_category_rules');
    }
};
