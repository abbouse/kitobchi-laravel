<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->decimal('ai_post_score', 3, 2)->nullable()->after('last_edited_by_id');
            $table->timestamp('ai_post_checked_at')->nullable()->after('ai_post_score');
            $table->string('ai_post_status', 24)->nullable()->after('ai_post_checked_at');
            $table->string('ai_post_note', 255)->nullable()->after('ai_post_status');
            $table->string('ai_post_model', 64)->nullable()->after('ai_post_note');
        });

        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->decimal('ai_score', 3, 2)->nullable()->after('parent_id');
            $table->timestamp('ai_checked_at')->nullable()->after('ai_score');
            $table->string('ai_status', 24)->nullable()->after('ai_checked_at');
            $table->string('ai_note', 255)->nullable()->after('ai_status');
            $table->string('ai_model', 64)->nullable()->after('ai_note');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedInteger('ugc_reviews_count')->default(0)->after('ugc_aggregate_score');
            $table->timestamp('ugc_last_scored_at')->nullable()->after('ugc_reviews_count');
        });

        Schema::table('stationeries', function (Blueprint $table) {
            $table->unsignedInteger('ugc_reviews_count')->default(0)->after('ugc_aggregate_score');
            $table->timestamp('ugc_last_scored_at')->nullable()->after('ugc_reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->dropColumn([
                'ai_post_score',
                'ai_post_checked_at',
                'ai_post_status',
                'ai_post_note',
                'ai_post_model',
            ]);
        });

        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->dropColumn([
                'ai_score',
                'ai_checked_at',
                'ai_status',
                'ai_note',
                'ai_model',
            ]);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['ugc_reviews_count', 'ugc_last_scored_at']);
        });

        Schema::table('stationeries', function (Blueprint $table) {
            $table->dropColumn(['ugc_reviews_count', 'ugc_last_scored_at']);
        });
    }
};
