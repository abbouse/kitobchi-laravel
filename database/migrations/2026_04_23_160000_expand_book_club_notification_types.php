<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `book_club_notifications`
            MODIFY `type` ENUM(
                'like',
                'comment',
                'new_post',
                'follow',
                'repost'
            ) NOT NULL
        ");
    }
};
