<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curated_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained('curated_collections')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('product_type', 32)->default('book');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['collection_id', 'sort_order']);
            $table->index(['product_type', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curated_collection_items');
    }
};
