<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_club', function (Blueprint $table) {
            $table->boolean('is_hidden_by_ai')->default(false)->after('ai_post_model');
            $table->string('ai_moderation_status', 32)->nullable()->after('is_hidden_by_ai');
            $table->timestamp('ai_moderated_at')->nullable()->after('ai_moderation_status');
            $table->string('ai_moderation_note', 500)->nullable()->after('ai_moderated_at');
            $table->string('ai_moderation_model', 64)->nullable()->after('ai_moderation_note');
            $table->json('ai_moderation_meta')->nullable()->after('ai_moderation_model');

            $table->index(['is_deleted', 'is_hidden_by_ai'], 'bc_public_moderation_idx');
            $table->index('ai_moderation_status', 'bc_ai_moderation_status_idx');
        });

        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->json('ai_moderation_meta')->nullable()->after('ai_moderation_model');
        });
    }

    public function down(): void
    {
        Schema::table('book_club_comments', function (Blueprint $table) {
            $table->dropColumn('ai_moderation_meta');
        });

        Schema::table('book_club', function (Blueprint $table) {
            $table->dropIndex('bc_public_moderation_idx');
            $table->dropIndex('bc_ai_moderation_status_idx');
            $table->dropColumn([
                'is_hidden_by_ai',
                'ai_moderation_status',
                'ai_moderated_at',
                'ai_moderation_note',
                'ai_moderation_model',
                'ai_moderation_meta',
            ]);
        });
    }
};
