<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BOOK CLUB FEED ENGAGEMENT — count subquery indekslari.
 *
 * Feed ranking (engagement_score) har post uchun 5 ta COUNT subquery ishlatadi:
 * likes, comments, votes, reposts, media. Bu jadvallarning `post_id` ustuni
 * indekslanmagan edi → har subquery to'liq jadval skani. Indekslar SUBQUERY'ni
 * tez qiladi, feed TARTIBI/natijasi umuman o'zgarmaydi.
 *
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('book_club_likes', 'bcl_post_idx', ['post_id']);
        $this->addIndex('book_club_images', 'bci_post_idx', ['post_id']);
        $this->addIndex('book_club_votes', 'bcv_post_idx', ['post_id']);
        // Top-level izohlar count'i uchun (post_id + parent_id IS NULL)
        $this->addIndex('book_club_comments', 'bcc_post_parent_idx', ['post_id', 'parent_id']);
    }

    public function down(): void
    {
        foreach ([
            'book_club_likes' => ['bcl_post_idx'],
            'book_club_images' => ['bci_post_idx'],
            'book_club_votes' => ['bcv_post_idx'],
            'book_club_comments' => ['bcc_post_parent_idx'],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndex($table, $index);
            }
        }
    }

    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }
        if ($this->indexExists($table, $name)) {
            return;
        }
        Schema::table($table, fn ($t) => $t->index($columns, $name));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            Schema::table($table, fn ($t) => $t->dropIndex($name));
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return ! empty(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$name]));
    }
};
