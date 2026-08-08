<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('book_club_notifications')) {
            return;
        }

        DB::statement("
            ALTER TABLE `book_club_notifications`
            MODIFY `type` ENUM(
                'like',
                'comment',
                'reply',
                'comment_like',
                'vote',
                'new_post',
                'follow',
                'repost',
                'mention'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('book_club_notifications')) {
            return;
        }

        DB::table('book_club_notifications')
            ->where('type', 'mention')
            ->update(['type' => 'comment']);

        DB::statement("
            ALTER TABLE `book_club_notifications`
            MODIFY `type` ENUM(
                'like',
                'comment',
                'reply',
                'comment_like',
                'vote',
                'new_post',
                'follow',
                'repost'
            ) NOT NULL
        ");
    }
};
