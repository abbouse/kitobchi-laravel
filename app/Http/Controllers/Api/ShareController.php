<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasProductVisibility;
use App\Models\BookClub;
use App\Models\BookClubLikes;
use App\Models\FavouriteProducts;
use App\Support\ProductPayloadFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    use HasProductVisibility;

    private function publicBookScope()
    {
        return $this->visibleBooks();
    }

    private function publicStationeryScope()
    {
        return $this->visibleStationeries();
    }

    private function err(string $msg, int $code = 404)
    {
        return response()->json(['status' => 'error', 'message' => $msg], $code);
    }

    // ── GET /api/share/product/{id}?type=book ─────────────────────────
    // Autentifikatsiyasiz ham ishlaydi
    public function product(Request $request, int $id)
    {
        $type = $request->query('type', 'book');
        $user = Auth::guard('user')->user(); // null bo'lishi mumkin

        if ($type === 'book') {
            $product = $this->publicBookScope()
                ->with(['seller', 'category', 'tags'])
                ->find($id);
        } else {
            $product = $this->publicStationeryScope()
                ->with(['seller', 'category', 'tags', 'variants'])
                ->find($id);
        }

        if (!$product) {
            return $this->err('Mahsulot topilmadi!');
        }

        // Favourite holati (faqat login qilingan user uchun)
        $isFavourite = false;
        if ($user) {
            $isFavourite = FavouriteProducts::where('user_id', $user->id)
                ->where('product_id', $id)
                ->where('product_type', $type)
                ->exists();
        }

        // ProductModel formatiga moslashtirish
        $data = $this->formatProduct($product, $type, $isFavourite);

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function productByArtikul(Request $request, string $artikul)
    {
        $normalizedArtikul = trim($artikul);
        if ($normalizedArtikul === '') {
            return $this->err('Mahsulot topilmadi!');
        }

        $user = Auth::guard('user')->user();

        $book = $this->publicBookScope()
            ->with(['seller', 'category', 'tags'])
            ->where('artikul', $normalizedArtikul)
            ->first();

        if ($book) {
            $isFavourite = false;
            if ($user) {
                $isFavourite = FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $book->id)
                    ->where('product_type', 'book')
                    ->exists();
            }

            return response()->json([
                'status' => 'success',
                'data'   => $this->formatProduct($book, 'book', $isFavourite),
            ]);
        }

        $stationery = $this->publicStationeryScope()
            ->with(['seller', 'category', 'tags', 'variants'])
            ->where('artikul', $normalizedArtikul)
            ->first();

        if ($stationery) {
            $isFavourite = false;
            if ($user) {
                $isFavourite = FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $stationery->id)
                    ->where('product_type', 'stationery')
                    ->exists();
            }

            return response()->json([
                'status' => 'success',
                'data'   => $this->formatProduct($stationery, 'stationery', $isFavourite),
            ]);
        }

        return $this->err('Mahsulot topilmadi!');
    }

    private function formatProduct($product, string $type, bool $isFavourite): array
    {
        $user = Auth::guard('user')->user();

        return ProductPayloadFormatter::format($product, [
            'type' => $type,
            'favourite' => $isFavourite,
            'mode' => 'detail',
            'category_format' => 'title',
            'extra' => [
                'ugc_reviews_preview' => $this->buildReviewPreview(
                    // GLOBAL KATALOG: sharhlar kitobniki — barcha do'kon takliflari bo'yicha
                    $type === 'book'
                        ? \App\Support\CatalogOffers::siblingIds($product->edition_id ? (int) $product->edition_id : null, (int) $product->id)
                        : [(int) $product->id],
                    $type,
                    $user?->id,
                ),
            ],
        ]);
    }

    private function buildReviewPreview(array $productIds, string $type, ?int $userId): array
    {
        $posts = BookClub::query()
            ->whereIn('product_id', $productIds)
            ->where('product_type', $type)
            ->where('is_deleted', false)
            ->with([
                'user:id,name,lastname,avatar,isVerified,isSupport,role_emoji,role_title,role_place',
            ])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->take(3)
            ->get();

        if ($posts->isEmpty()) {
            return [];
        }

        $likedPostIds = $userId
            ? BookClubLikes::where('user_id', $userId)
                ->whereIn('post_id', $posts->pluck('id'))
                ->pluck('post_id')
                ->all()
            : [];

        $likedMap = array_flip($likedPostIds);

        return $posts->map(function (BookClub $post) use ($likedMap) {
            return [
                'id' => $post->id,
                'text' => $post->text,
                'created_at' => optional($post->created_at)?->toIso8601String(),
                'likes_count' => (int) ($post->likes_count ?? 0),
                'comments_count' => (int) ($post->comments_count ?? 0),
                'liked_by_me' => isset($likedMap[$post->id]),
                'ai_post_score' => $post->ai_post_score !== null
                    ? (float) $post->ai_post_score
                    : null,
                'user' => $post->user ? [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'lastname' => $post->user->lastname,
                    'avatar' => $post->user->avatar,
                    'isVerified' => (bool) ($post->user->isVerified ?? false),
                    'isSupport' => (bool) ($post->user->isSupport ?? false),
                    'role_emoji' => $post->user->role_emoji,
                    'role_title' => $post->user->role_title,
                    'role_place' => $post->user->role_place,
                ] : null,
            ];
        })->values()->toArray();
    }
}
