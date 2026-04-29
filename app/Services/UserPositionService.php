<?php

namespace App\Services;

use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\FavouriteProducts;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Models\User;
use App\Models\UserPositionHistory;
use Illuminate\Support\Facades\DB;

class UserPositionService
{
    public const POSITIONS = [
        'reader',
        'active_reader',
        'book_lover',
        'reviewer',
        'collector',
        'book_club_star',
        'market_explorer',
        'literary_mentor',
    ];

    private const RULES = [
        'active_reader' => [
            'account_age_days' => 3,
            'min_days_in_current' => 0,
            'completed_orders' => 1,
        ],
        'book_lover' => [
            'account_age_days' => 7,
            'min_days_in_current' => 5,
            'completed_orders' => 3,
            'favourites_count' => 5,
        ],
        'reviewer' => [
            'account_age_days' => 14,
            'min_days_in_current' => 7,
            'completed_orders' => 5,
            'posts_count' => 2,
            'comments_count' => 8,
        ],
        'collector' => [
            'account_age_days' => 21,
            'min_days_in_current' => 10,
            'completed_orders' => 10,
            'favourites_count' => 12,
            'unique_sellers_purchased' => 3,
        ],
        'book_club_star' => [
            'account_age_days' => 30,
            'min_days_in_current' => 10,
            'posts_count' => 5,
            'comments_count' => 20,
            'likes_received' => 25,
            'followers_count' => 10,
        ],
        'market_explorer' => [
            'account_age_days' => 45,
            'min_days_in_current' => 14,
            'completed_orders' => 15,
            'unique_sellers_purchased' => 5,
            'favourites_count' => 20,
        ],
        'literary_mentor' => [
            'account_age_days' => 90,
            'min_days_in_current' => 21,
            'completed_orders' => 25,
            'posts_count' => 10,
            'comments_count' => 35,
            'likes_received' => 60,
            'followers_count' => 30,
            'unique_sellers_purchased' => 6,
        ],
    ];

    public function evaluateAndPromote(User $user, ?string $reason = null): ?string
    {
        $user->refresh();

        if ($user->isBlocked()) {
            return null;
        }

        $current = $this->normalizePosition($user->position);
        $currentIndex = array_search($current, self::POSITIONS, true);
        if ($currentIndex === false) {
            $current = 'reader';
            $currentIndex = 0;
        }

        if ($currentIndex >= count(self::POSITIONS) - 1) {
            return null;
        }

        $next = self::POSITIONS[$currentIndex + 1];
        $metrics = $this->metrics($user);

        if (!$this->qualifies($next, $metrics)) {
            return null;
        }

        $user->forceFill([
            'position' => $next,
            'position_earned_at' => now(),
        ])->save();

        UserPositionHistory::create([
            'user_id' => $user->id,
            'from_position' => $current,
            'to_position' => $next,
            'metrics_snapshot' => $metrics,
            'reason' => $reason ?: 'auto_progression',
            'awarded_at' => now(),
        ]);

        return $next;
    }

    public function metrics(User $user): array
    {
        $accountAgeDays = (int) $user->created_at?->diffInDays(now()) ?: 0;
        $daysInCurrent = (int) ($user->position_earned_at?->diffInDays(now()) ?? $accountAgeDays);

        $completedOrders = Sold::query()
            ->where('user_id', $user->id)
            ->where('status', 'C')
            ->count();

        $uniqueSellersPurchased = SellerOrder::query()
            ->where('client_id', $user->id)
            ->where('status', 3)
            ->distinct('seller_id')
            ->count('seller_id');

        $postsCount = BookClub::query()
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->count();

        $commentsCount = BookClubComment::query()
            ->where('user_id', $user->id)
            ->count();

        $favouritesCount = FavouriteProducts::query()
            ->where('user_id', $user->id)
            ->count();

        $followersCount = DB::table('user_follows')
            ->where('following_id', $user->id)
            ->count();

        $postLikesReceived = DB::table('book_club_likes')
            ->join('book_club', 'book_club.id', '=', 'book_club_likes.post_id')
            ->where('book_club.user_id', $user->id)
            ->count();

        $commentLikesReceived = DB::table('book_club_comment_likes')
            ->join('book_club_comments', 'book_club_comments.id', '=', 'book_club_comment_likes.comment_id')
            ->where('book_club_comments.user_id', $user->id)
            ->count();

        return [
            'account_age_days' => $accountAgeDays,
            'days_in_current_position' => $daysInCurrent,
            'completed_orders' => $completedOrders,
            'unique_sellers_purchased' => $uniqueSellersPurchased,
            'posts_count' => $postsCount,
            'comments_count' => $commentsCount,
            'favourites_count' => $favouritesCount,
            'followers_count' => $followersCount,
            'likes_received' => $postLikesReceived + $commentLikesReceived,
        ];
    }

    private function qualifies(string $position, array $metrics): bool
    {
        $rules = self::RULES[$position] ?? [];

        foreach ($rules as $key => $required) {
            $metricKey = $key === 'min_days_in_current'
                ? 'days_in_current_position'
                : $key;

            if (($metrics[$metricKey] ?? 0) < $required) {
                return false;
            }
        }

        return true;
    }

    private function normalizePosition(?string $position): string
    {
        $normalized = trim((string) $position);

        return in_array($normalized, self::POSITIONS, true) ? $normalized : 'reader';
    }
}
