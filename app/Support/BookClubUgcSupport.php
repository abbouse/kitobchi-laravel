<?php

namespace App\Support;

use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Facades\DB;

/**
 * Book club izohlardan mahsulot UGC o‘rtacha bahosini yangilash.
 */
class BookClubUgcSupport
{
    /**
     * @param  list<int>  $postIds
     */
    public static function recalcProductUgcFromPosts(array $postIds): void
    {
        $postIds = array_values(array_unique(array_filter($postIds)));
        if ($postIds === []) {
            return;
        }

        $rows = BookClub::query()
            ->whereIn('id', $postIds)
            ->whereNotNull('product_id')
            ->get(['product_id', 'product_type']);

        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            if ($pid <= 0) {
                continue;
            }
            $ptype = $row->product_type;
            $avg = DB::table('book_club_comments as c')
                ->join('book_club as p', 'p.id', '=', 'c.post_id')
                ->where('p.product_type', $ptype)
                ->where('p.product_id', $pid)
                ->where('p.is_deleted', 0)
                ->whereNull('c.parent_id')
                ->whereNotNull('c.kangaroo_star_equivalent')
                ->avg('c.kangaroo_star_equivalent');

            if ($avg === null) {
                continue;
            }
            $val = round((float) $avg, 2);
            if ($ptype === 'book') {
                Books::query()->where('id', $pid)->update(['ugc_aggregate_score' => $val]);
            } elseif ($ptype === 'stationery') {
                Stationery::query()->where('id', $pid)->update(['ugc_aggregate_score' => $val]);
            }
        }
    }

    /**
     * Post uchun izohlardan o‘rtacha yulduz (faqat baholangan izohlar).
     */
    public static function recalcPostStarFromComments(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }
        $avg = BookClubComment::query()
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->whereNotNull('kangaroo_star_equivalent')
            ->avg('kangaroo_star_equivalent');
        if ($avg !== null) {
            BookClub::query()->where('id', $postId)->update([
                'kangaroo_post_star' => round((float) $avg, 2),
                'kangaroo_post_checked_at' => now(),
            ]);
        }
    }
}
