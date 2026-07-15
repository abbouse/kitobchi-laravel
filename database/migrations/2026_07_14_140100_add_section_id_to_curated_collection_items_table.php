<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('curated_collection_items') || Schema::hasColumn('curated_collection_items', 'section_id')) {
            return;
        }

        Schema::table('curated_collection_items', function (Blueprint $table) {
            // null = mahsulot to'g'ridan-to'g'ri to'plamda (bo'limsiz, default rejim).
            $table->unsignedBigInteger('section_id')->nullable()->after('collection_id')->index();

            if (Schema::hasTable('curated_collection_sections')) {
                $table->foreign('section_id')->references('id')->on('curated_collection_sections')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('curated_collection_items') || ! Schema::hasColumn('curated_collection_items', 'section_id')) {
            return;
        }

        Schema::table('curated_collection_items', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
        });
    }
};
