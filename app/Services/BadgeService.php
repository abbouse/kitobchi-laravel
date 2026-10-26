<?php

namespace App\Services;

use App\Enums\PaymentStatusCode;
use App\Models\Books;
use App\Models\Sold;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Klub nishonlari: faollik uchun beriladi, imtiyoz bermaydi va qaytarib
 * olinmaydi. Nomi va tavsifi ilovaning tarjima fayllarida (badge_{key}_title,
 * badge_{key}_desc); server faqat kalit, sana va progressni qaytaradi.
 *
 * Aldashga chidamlilik: layklar faqat BOSHQA odamlardan, o'chirilgan
 * postlar sanalmaydi, xaridlar faqat yetkazilgan va to'langanlari.
 */
class BadgeService
{
    /** Tartib — ilovadagi ko'rinish tartibi. metric => target */
    public const DEFINITIONS = [
        'first_order' => ['metric' => 'orders', 'target' => 1],
        'bookworm' => ['metric' => 'books', 'target' => 10],
        'collector' => ['metric' => 'books', 'target' => 50],
        'genre_explorer' => ['metric' => 'categories', 'target' => 5],
        'shop_explorer' => ['metric' => 'sellers', 'target' => 5],
        'reviewer' => ['metric' => 'reviews', 'target' => 5],
        'club_voice' => ['metric' => 'posts', 'target' => 10],
        'conversationalist' => ['metric' => 'comments', 'target' => 30],
        'club_star' => ['metric' => 'likes', 'target' => 100],
        'inspirer' => ['metric' => 'followers', 'target' => 50],
        'lucky' => ['metric' => 'game_books', 'target' => 1],
        'veteran' => ['metric' => 'account_days', 'target' => 365],
    ];

    public function evaluate(User $user): array
    {
        if (! Schema::hasTable('user_badges')) {
            return [];
        }
        $have = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge_key')->all();
        $missing = array_diff(array_keys(self::DEFINITIONS), $have);
        if (! $missing) {
            return [];
        }
        $m = $this->metrics($user);
        $new = [];
        foreach ($missing as $key) {
            $d = self::DEFINITIONS[$key];
            if (($m[$d['metric']] ?? 0) >= $d['target']) {
                $new[] = ['user_id' => $user->id, 'badge_key' => $key, 'awarded_at' => now()];
            }
        }
        if ($new) {
            DB::table('user_badges')->insertOrIgnore($new);
        }

        return array_column($new, 'badge_key');
    }

    /** Ilova uchun ro'yxat. Progress faqat egasiga ($withProgress). */
    public function list(User $user, bool $withProgress): array
    {
        $earned = Schema::hasTable('user_badges')
            ? DB::table('user_badges')->where('user_id', $user->id)->pluck('awarded_at', 'badge_key')->all()
            : [];
        $m = $withProgress ? $this->metrics($user) : [];

        $out = [];
        foreach (self::DEFINITIONS as $key => $d) {
            $at = $earned[$key] ?? null;
            $row = [
                'key' => $key,
                'earned' => $at !== null,
                'awarded_at' => $at ? \Illuminate\Support\Carbon::parse($at)->toIso8601String() : null,
                'target' => $d['target'],
            ];
            if ($withProgress) {
                $row['current'] = min($d['target'], (int) ($m[$d['metric']] ?? 0));
            }
            $out[] = $row;
        }

        return $out;
    }

    /** Eng oxirgi olingan nishon (profilda avatar ustida). */
    public function latest(int $userId): ?array
    {
        if (! Schema::hasTable('user_badges')) {
            return null;
        }
        $row = DB::table('user_badges')->where('user_id', $userId)
            ->orderByDesc('awarded_at')->orderByDesc('id')->first(['badge_key', 'awarded_at']);

        return $row ? ['key' => $row->badge_key, 'awarded_at' => \Illuminate\Support\Carbon::parse($row->awarded_at)->toIso8601String()] : null;
    }

    public function count(int $userId): int
    {
        return Schema::hasTable('user_badges') ? DB::table('user_badges')->where('user_id', $userId)->count() : 0;
    }

    public function metrics(User $user): array
    {
        $orders = Sold::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where(function ($q) {
                $q->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(fn ($f) => $f->whereNull('payment_status_code')->where('paymentStatus', PaymentStatusCode::PAID->legacy()));
            })
            ->get(['items']);

        $bookIds = [];
        foreach ($orders as $o) {
            foreach ((array) $o->items as $item) {
                if (($item['type'] ?? 'book') === 'book' && (int) ($item['item_id'] ?? 0) > 0) {
                    $bookIds[(int) $item['item_id']] = true;
                }
            }
        }
        $books = $bookIds
            ? Books::withoutGlobalScopes()->whereIn('id', array_keys($bookIds))->get(['id', 'edition_id', 'category_id', 'seller_id'])
            : collect();

        $uid = $user->id;
        $postLikes = DB::table('book_club_likes')
            ->join('book_club', 'book_club.id', '=', 'book_club_likes.post_id')
            ->where('book_club.user_id', $uid)->where('book_club_likes.user_id', '!=', $uid)
            ->where('book_club.is_deleted', false)->count();
        $commentLikes = Schema::hasTable('book_club_comment_likes')
            ? DB::table('book_club_comment_likes')
                ->join('book_club_comments', 'book_club_comments.id', '=', 'book_club_comment_likes.comment_id')
                ->where('book_club_comments.user_id', $uid)->where('book_club_comment_likes.user_id', '!=', $uid)->count()
            : 0;

        return [
            'orders' => $orders->count(),
            'books' => $books->map(fn ($b) => $b->edition_id ? 'e'.$b->edition_id : 'o'.$b->id)->unique()->count(),
            'categories' => $books->pluck('category_id')->filter()->unique()->count(),
            'sellers' => $books->pluck('seller_id')->filter()->unique()->count(),
            'reviews' => DB::table('book_club')->where('user_id', $uid)->where('is_deleted', false)
                ->whereNotNull('product_id')->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', '!=', 'order'))->count(),
            'posts' => DB::table('book_club')->where('user_id', $uid)->where('is_deleted', false)->where('repost', false)->count(),
            'comments' => DB::table('book_club_comments')->where('user_id', $uid)->count(),
            'likes' => $postLikes + $commentLikes,
            'followers' => DB::table('user_follows')->where('following_id', $uid)->where('follower_id', '!=', $uid)->count(),
            'game_books' => Schema::hasTable('game_rewards')
                ? DB::table('game_rewards')->where('user_id', $uid)->where('type', 'book')->count() : 0,
            'account_days' => (int) ($user->created_at?->diffInDays(now()) ?? 0),
        ];
    }
}
