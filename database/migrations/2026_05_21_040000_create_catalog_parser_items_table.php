<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_parser_items', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 64)->index();
            $table->string('external_id')->nullable()->index();
            $table->string('source_url')->unique();
            $table->string('title')->nullable()->index();
            $table->string('author')->nullable()->index();
            $table->string('isbn', 32)->nullable()->index();
            $table->string('normalized_title')->nullable()->index();
            $table->string('normalized_author')->nullable()->index();
            $table->string('source_category')->nullable();
            $table->string('publisher')->nullable();
            $table->string('translator')->nullable();
            $table->string('language')->nullable();
            $table->string('script')->nullable();
            $table->string('cover_type')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('price_uzs')->nullable();
            $table->decimal('rating_value', 4, 2)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->boolean('in_stock')->default(false)->index();
            $table->string('primary_image_url')->nullable();
            $table->json('remote_image_urls')->nullable();
            $table->longText('description')->nullable();
            $table->json('payload')->nullable();
            // Legacy product tables in production may use non-bigint IDs,
            // so we keep parser references soft and index-only here.
            $table->unsignedInteger('matched_book_id')->nullable()->index();
            $table->unsignedSmallInteger('match_confidence')->nullable();
            $table->string('match_reason')->nullable();
            $table->unsignedInteger('suggested_category_id')->nullable()->index();
            $table->string('suggested_category_name')->nullable();
            $table->json('category_ai_payload')->nullable();
            $table->unsignedInteger('imported_book_id')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamp('imported_at')->nullable();
            $table->text('last_import_error')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id'], 'catalog_parser_items_provider_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_parser_items');
    }
};
