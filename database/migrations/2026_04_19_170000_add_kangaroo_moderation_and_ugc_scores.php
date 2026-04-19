<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('kangaroo_listing_decision', 24)->nullable()->after('is_approved');
            $table->unsignedTinyInteger('kangaroo_listing_score')->nullable()->after('kangaroo_listing_decision');
            $table->timestamp('kangaroo_listing_checked_at')->nullable()->after('kangaroo_listing_score');
            $table->json('kangaroo_listing_issues')->nullable()->after('kangaroo_listing_checked_at');
            $table->decimal('ugc_aggregate_score', 3, 2)->default(0)->after('kangaroo_listing_issues');
        });

        Schema::table('stationeries', function (Blueprint $table) {
            $table->string('kangaroo_listing_decision', 24)->nullable()->after('is_approved');
            $table->unsignedTinyInteger('kangaroo_listing_score')->nullable()->after('kangaroo_listing_decision');
            $table->timestamp('kangaroo_listing_checked_at')->nullable()->after('kangaroo_listing_score');
            $table->json('kangaroo_listing_issues')->nullable()->after('kangaroo_listing_checked_at');
            $table->decimal('ugc_aggregate_score', 3, 2)->default(0)->after('kangaroo_listing_issues');
        });

        Schema::table('book_club', function (Blueprint $table) {
            $table->decimal('kangaroo_post_star', 3, 2)->nullable()->after('is_deleted');
            $table->timestamp('kangaroo_post_checked_at')->nullable()->after('kangaroo_post_star');
        });

        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->decimal('kangaroo_star_equivalent', 3, 2)->nullable()->after('content');
            $table->decimal('kangaroo_toxicity', 4, 3)->nullable()->after('kangaroo_star_equivalent');
            $table->timestamp('kangaroo_checked_at')->nullable()->after('kangaroo_toxicity');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'kangaroo_listing_decision',
                'kangaroo_listing_score',
                'kangaroo_listing_checked_at',
                'kangaroo_listing_issues',
                'ugc_aggregate_score',
            ]);
        });

        Schema::table('stationeries', function (Blueprint $table) {
            $table->dropColumn([
                'kangaroo_listing_decision',
                'kangaroo_listing_score',
                'kangaroo_listing_checked_at',
                'kangaroo_listing_issues',
                'ugc_aggregate_score',
            ]);
        });

        Schema::table('book_club', function (Blueprint $table) {
            $table->dropColumn(['kangaroo_post_star', 'kangaroo_post_checked_at']);
        });

        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->dropColumn(['kangaroo_star_equivalent', 'kangaroo_toxicity', 'kangaroo_checked_at']);
        });
    }
};
