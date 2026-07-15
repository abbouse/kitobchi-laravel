<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curated_collection_sections')) {
            return;
        }

        Schema::create('curated_collection_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('curated_collections')->cascadeOnDelete();
            // parent_id — 2-daraja uchun (mas. 1-sinf ichida "qiz bola"). null = 1-daraja bo'lim.
            $table->unsignedBigInteger('parent_id')->nullable()->index();

            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('name_ja')->nullable();

            // Har bo'limga alohida narx (leaf bo'lim uchun). null = narx belgilanmagan.
            $table->integer('custom_total_price')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['collection_id', 'parent_id', 'sort_order'], 'ccs_collection_parent_sort_idx');
            $table->foreign('parent_id')->references('id')->on('curated_collection_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curated_collection_sections');
    }
};
