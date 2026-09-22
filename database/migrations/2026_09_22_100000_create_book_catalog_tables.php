<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GLOBAL KITOB KATALOGI (Amazon ASIN / offer modeli).
 *
 *  book_editions            — kitobning o'zi (bitta nashr), barcha do'konlar uchun umumiy.
 *  books                    — do'kon TAKLIFI (narx, qoldiq, holat). `books.id` o'zgarmaydi,
 *                             savat/buyurtma/stock/reklama avvalgidek shu id bilan ishlaydi.
 *  books.edition_id         — taklif qaysi kitobga tegishli.
 *  books.catalog_featured   — mijoz ro'yxatlarida kitob nomidan chiqadigan YAGONA taklif
 *                             ("buy box"). Default 1 — migratsiyadan keyin hech narsa
 *                             o'zgarmaydi, dedupe faqat taklif edition'ga ulanganda boshlanadi.
 *  book_edition_submissions — do'kon yuborgan yangi kitob arizasi (old/orqa muqova, ISBN tasdig'i).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_editions', function (Blueprint $table) {
            $table->id();
            $table->string('isbn13', 13)->nullable()->index();
            $table->string('isbn10', 10)->nullable()->index();
            $table->string('title');
            $table->string('author')->nullable();
            $table->unsignedInteger('author_id')->nullable()->index();
            $table->string('translator')->nullable();
            $table->unsignedInteger('publisher_id')->nullable()->index();
            $table->integer('category_id')->nullable()->index();
            $table->string('lang', 20)->nullable();
            $table->string('langType', 10)->nullable();
            $table->string('coverType', 10)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->text('description')->nullable();
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            $table->json('images')->nullable();
            $table->json('tag_ids')->nullable();
            // pending | active | rejected | merged
            $table->string('status', 16)->default('active')->index();
            $table->unsignedBigInteger('merged_into_id')->nullable()->index();
            // backfill | admin | seller | parser | legacy
            $table->string('source', 16)->default('admin');
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('match_key', 191)->nullable()->index();
            // Buy box natijasi (BuyBoxService yozadi)
            $table->unsignedInteger('offers_count')->default(0);
            $table->unsignedInteger('in_stock_offers_count')->default(0);
            $table->unsignedInteger('min_price')->nullable();
            $table->unsignedInteger('featured_book_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'deleted_at']);
        });

        Schema::create('book_edition_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('edition_id')->nullable()->index();
            $table->unsignedInteger('book_id')->nullable()->index();
            $table->string('isbn13', 13)->nullable()->index();
            // Orqa muqovadagi shtrix-koddan o'qilgan ISBN: server (zbar / AI) va ilova (ML Kit)
            $table->string('back_isbn_server', 13)->nullable();
            $table->string('back_isbn_server_method', 16)->nullable();
            $table->string('back_isbn_client', 13)->nullable();
            // matched | mismatch | unreadable | no_isbn
            $table->string('isbn_check', 16)->nullable();
            $table->json('payload')->nullable();
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            // pending | approved | rejected | merged
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('edition_id')->nullable()->index();
            $table->boolean('catalog_featured')->default(true);
            // new | used_good | used_fair
            $table->string('condition', 16)->default('new');
            // Admin "o'chirish" — buyurtma tarixi saqlanishi uchun arxivlash
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();

            $table->index(['catalog_featured', 'is_approved', 'is_hidden', 'status'], 'books_catalog_featured_idx');
            $table->index(['edition_id', 'seller_id'], 'books_edition_seller_idx');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('books_catalog_featured_idx');
            $table->dropIndex('books_edition_seller_idx');
            $table->dropIndex(['edition_id']);
            $table->dropColumn(['edition_id', 'catalog_featured', 'condition', 'archived_at', 'archived_by']);
        });
        Schema::dropIfExists('book_edition_submissions');
        Schema::dropIfExists('book_editions');
    }
};
