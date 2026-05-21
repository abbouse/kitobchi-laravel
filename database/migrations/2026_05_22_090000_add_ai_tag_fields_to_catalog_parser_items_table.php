<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_parser_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalog_parser_items', 'suggested_tag_ids')) {
                $table->json('suggested_tag_ids')->nullable()->after('suggested_category_name');
            }
            if (!Schema::hasColumn('catalog_parser_items', 'suggested_tag_names')) {
                $table->json('suggested_tag_names')->nullable()->after('suggested_tag_ids');
            }
            if (!Schema::hasColumn('catalog_parser_items', 'tags_ai_payload')) {
                $table->json('tags_ai_payload')->nullable()->after('suggested_tag_names');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalog_parser_items', function (Blueprint $table) {
            foreach (['tags_ai_payload', 'suggested_tag_names', 'suggested_tag_ids'] as $column) {
                if (Schema::hasColumn('catalog_parser_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
