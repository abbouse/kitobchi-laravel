<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\Books;
use App\Models\Stationery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductUgcRatingService
{
    private const PRIOR_MEAN = 4.0;
    private const PRIOR_WEIGHT = 3.0;

    /**
     * @param list<int> $postIds
     */
    public function refreshFromPostIds(array $postIds): void
    {
        $products = BookClub::query()
            ->whereIn('id', array_values(array_unique(array_filter($postIds))))
            ->where(function ($query) {
                $query->where('repost', false)->orWhereNull('repost');
            })
            ->whereNotNull('product_id')
            ->whereIn('product_type', ['book', 'stationery'])
            ->get(['product_id', 'product_type'])
            ->map(fn (BookClub $post) => [
                'product_id' => (int) $post->product_id,
                'product_type' => (string) $post->product_type,
            ])
            ->unique(fn (array $row) => $row['product_type'].'_'.$row['product_id'])
            ->values();

        $this->refreshProducts($products);
    }

    public function refreshAll(): void
    {
        $products = BookClub::query()
            ->where('is_deleted', false)
            ->where(function ($query) {
                $query->where('repost', false)->orWhereNull('repost');
            })
            ->whereNotNull('product_id')
            ->whereIn('product_type', ['book', 'stationery'])
            ->get(['product_id', 'product_type'])
            ->map(fn (BookClub $post) => [
                'product_id' => (int) $post->product_id,
                'product_type' => (string) $post->product_type,
            ])
            ->unique(fn (array $row) => $row['product_type'].'_'.$row['product_id'])
            ->values();

        $this->refreshProducts($products);
    }

    /**
     * @param Collection<int, array{product_id:int,product_type:string}> $products
     */
    private function refreshProducts(Collection $products): void
    {
        foreach ($products as $row) {
            $productId = (int) $row['product_id'];
            $productType = (string) $row['product_type'];

            if ($productId <= 0) {
                continue;
            }

            $posts = DB::table('book_club as p')
                ->where('p.is_deleted', 0)
                ->where(function ($query) {
                    $query->whereNull('p.is_hidden_by_ai')->orWhere('p.is_hidden_by_ai', false);
                })
                ->where(function ($query) {
                    $query->where('p.repost', false)->orWhereNull('p.repost');
                })
                ->where('p.product_type', $productType)
                ->where('p.product_id', $productId)
                ->where('p.ai_post_status', 'scored')
                ->whereNotNull('p.ai_post_score')
                ->select('p.id', 'p.ai_post_score', 'p.created_at')
                ->get();

            if ($posts->isEmpty()) {
                $this->updateProductScore($productType, $productId, 0, 0);
                continue;
            }

            $comments = DB::table('book_club_comments as c')
                ->join('book_club as p', 'p.id', '=', 'c.post_id')
                ->where('p.is_deleted', 0)
                ->where(function ($query) {
                    $query->whereNull('p.is_hidden_by_ai')->orWhere('p.is_hidden_by_ai', false);
                })
                ->where(function ($query) {
                    $query->where('p.repost', false)->orWhereNull('p.repost');
                })
                ->where('p.product_type', $productType)
                ->where('p.product_id', $productId)
                ->where('c.ai_status', 'scored')
                ->whereNotNull('c.ai_score')
                ->where(function ($query) {
                    $query->whereNull('c.is_hidden_by_ai')->orWhere('c.is_hidden_by_ai', false);
                })
                ->select('c.post_id', 'c.ai_score', 'c.parent_id', 'c.created_at')
                ->get();

            $weightedScore = 0.0;
            $weightedCount = 0.0;

            foreach ($posts as $post) {
                $weight = $this->freshnessWeight($post->created_at);

                $weightedScore += ((float) $post->ai_post_score) * $weight;
                $weightedCount += $weight;
            }

            foreach ($comments as $comment) {
                // Postlar product uchun asosiy review signal.
                // Post ostidagi izohlar esa faqat ozgina ta'sir qiladi.
                $weight = $comment->parent_id ? 0.08 : 0.18;
                $weight *= $this->freshnessWeight($comment->created_at);

                $weightedScore += ((float) $comment->ai_score) * $weight;
                $weightedCount += $weight;
            }

            $bayesian = ($weightedScore + (self::PRIOR_MEAN * self::PRIOR_WEIGHT))
                / max(1.0, $weightedCount + self::PRIOR_WEIGHT);

            $this->updateProductScore(
                $productType,
                $productId,
                round(max(1, min(5, $bayesian)), 1),
                $posts->count(),
            );
        }
    }

    private function freshnessWeight($createdAt): float
    {
        $carbon = $createdAt ? Carbon::parse($createdAt) : null;
        if (! $carbon) {
            return 1.0;
        }

        $days = $carbon->diffInDays(now());
        if ($days <= 30) {
            return 1.25;
        }

        if ($days <= 90) {
            return 1.10;
        }

        return 1.0;
    }

    private function updateProductScore(string $productType, int $productId, float $score, int $reviewsCount): void
    {
        if ($productType === 'book') {
            if (! Schema::hasColumn('books', 'ugc_aggregate_score')) {
                return;
            }

            $payload = ['ugc_aggregate_score' => $score];
            if (Schema::hasColumn('books', 'ugc_reviews_count')) {
                $payload['ugc_reviews_count'] = max(0, $reviewsCount);
            }
            if (Schema::hasColumn('books', 'ugc_last_scored_at')) {
                $payload['ugc_last_scored_at'] = now();
            }

            Books::query()->whereKey($productId)->update($payload);
            return;
        }

        if ($productType === 'stationery') {
            if (! Schema::hasColumn('stationeries', 'ugc_aggregate_score')) {
                return;
            }

            $payload = ['ugc_aggregate_score' => $score];
            if (Schema::hasColumn('stationeries', 'ugc_reviews_count')) {
                $payload['ugc_reviews_count'] = max(0, $reviewsCount);
            }
            if (Schema::hasColumn('stationeries', 'ugc_last_scored_at')) {
                $payload['ugc_last_scored_at'] = now();
            }

            Stationery::query()->whereKey($productId)->update($payload);
        }
    }
}
