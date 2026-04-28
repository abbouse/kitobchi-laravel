<?php

namespace App\Http\Controllers\Api;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Services\BookClubModerationService;
use App\Services\BookClubNotificationTextService;
use App\Models\{User, Books, Stationery, BookClub, BookClubImages, BookClubLikes, BookClubVotes, BookClubComment, FavouriteProducts, BookClubNotification, SharedCart, StationeryVariant};
use App\Models\Sold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage, Auth};
use Illuminate\Support\Str;

class BookClubController extends Controller
{
    public function __construct(
        private readonly BookClubNotificationTextService $notificationTextService,
        private readonly BookClubModerationService $moderationService,
    ) {}

    /**
     * Mahsulotni API uchun formatlash
     * product_type = 'order' bo'lsa Sold modelidan olamiz
     */
    private function formatProduct($product, $user = null, ?string $productType = 'book')
    {
        if (!$product) return null;

        $productType = $productType ?: 'book';

        // ── Order ──────────────────────────────────────────────────────────────
        if ($productType === 'order') {
            return [
                'id'           => $product->id,
                'product_type' => 'order',
                'name'         => null, // order uchun name yo'q
                'order_id'     => $product->id,
                'amount'       => $product->amount,
                'items'        => $product->items ?? [],
                'status'       => $product->status,
            ];
        }

        // ── Kitob yoki kantselyariya ────────────────────────────────────────────
        $isBook = $product instanceof Books;

        return [
            'id'              => $product->id,
            'name'            => $product->name ?? null,
            'author'          => $isBook ? ($product->author ?? null) : null,
            'material'        => !$isBook ? ($product->material ?? null) : null,
            'category_id'     => $product->category_id ?? null,
            'images'          => is_array($product->images) ? $product->images : json_decode($product->images ?? '[]', true),
            'description'     => $product->description ?? null,
            'price'           => $product->price ?? 0,
            'count'           => $isBook ? ($product->count ?? 0) : ($product->stock ?? 0),
            'sales'           => $product->totalSales ?? 0,
            'weekly_sales'    => $product->totalSalesWeek ?? 0,
            'lang'            => $isBook ? ($product->lang ?? 'O\'zbek') : null,
            'langType'        => $isBook ? ($product->langType ?? '') : null,
            'coverType'       => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year'            => $isBook ? ($product->year ?? now()->year) : null,
            'discountPrice'   => $isBook ? ($product->discountPrice ?? null) : ($product->discount_price ?? null),
            'product_type'    => $isBook ? 'book' : 'stationery',
            'favourite'       => $user ? FavouriteProducts::where('user_id', $user->id)
                ->where('product_id', $product->id)->exists() : false,
            'category'        => $product->category->title ?? null,
            'tags'            => ($product->tags ?? collect())->map(function ($tag) {
                return [
                    'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
                    'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
                    'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
                    'ja' => $tag->tag_name_ja ?? $tag->name_ja ?? null,
                ];
            })->filter()->values(),
            'seller'          => [
                'seller_id'   => $product->seller->id ?? null,
                'shop_name'   => $product->seller->shop_name ?? null,
                'photo'       => $product->seller->photo ?? null,
                'isVerified'  => $product->seller->isVerified ?? null,
            ],
        ];
    }

    /**
     * Postlarga qo'shimcha ma'lumotlar qo'shish
     */
    private function attachMetaToPosts($posts, $user = null)
    {
        $postIds = $posts->pluck('id')->toArray();
        $followingIds = $user ? $user->followings()->pluck('users.id')->toArray() : [];
        $myReposts = $user ? BookClub::where('user_id', $user->id)
            ->where('repost', true)
            ->where('is_deleted', false)
            ->get(['reposted_user_id', 'product_id', 'text'])
            ->map(function ($r) {
                return $r->reposted_user_id . '_' . $r->product_id . '_' . md5($r->text);
            })->toArray() : [];

        // ── Mahsulotlar uchun eager loading ────────────────────────────────────
        $bookIds   = $posts->where('product_type', 'book')->pluck('product_id')->filter()->unique();
        $statIds   = $posts->where('product_type', 'stationery')->pluck('product_id')->filter()->unique();
        $orderIds  = $posts->where('product_type', 'order')->pluck('product_id')->filter()->unique();

        $books       = Books::with(['category', 'tags', 'seller'])->whereIn('id', $bookIds)->get()->keyBy('id');
        $stationeries = Stationery::with(['category', 'tags', 'seller'])->whereIn('id', $statIds)->get()->keyBy('id');
        // Order uchun faqat id, amount, items, status kerak
        $orders     = $orderIds->isNotEmpty()
            ? Sold::whereIn('id', $orderIds)->get(['id', 'amount', 'items', 'status'])->keyBy('id')
            : collect();

        // Repostlar sonini hisoblash
        $repostCounts = BookClub::where('repost', true)
            ->whereIn('reposted_user_id', $posts->pluck('user_id'))
            ->where('is_deleted', false)
            ->select('reposted_user_id', DB::raw('count(*) as count'))
            ->groupBy('reposted_user_id')
            ->pluck('count', 'reposted_user_id');

        // Metrikalar
        $voteCounts = DB::table('book_club_voted_users')
            ->whereIn('post_id', $postIds)
            ->select('option_id', DB::raw('count(*) as count'))
            ->groupBy('option_id')
            ->pluck('count', 'option_id');

        $userVotes = $user ? DB::table('book_club_voted_users')
            ->where('user_id', $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('option_id')
            ->flip() : collect();

        $commentsCount = BookClubComment::whereIn('post_id', $postIds)
            ->select('post_id', DB::raw('count(*) as count'))
            ->groupBy('post_id')
            ->pluck('count', 'post_id');

        $likesCount = BookClubLikes::whereIn('post_id', $postIds)
            ->select('post_id', DB::raw('count(*) as count'))
            ->groupBy('post_id')
            ->pluck('count', 'post_id');

        $userLikes = $user ? BookClubLikes::where('user_id', $user->id)
            ->whereIn('post_id', $postIds)
            ->pluck('post_id')
            ->flip() : collect();

        return $posts->map(function ($post) use (
            $user,
            $books,
            $stationeries,
            $orders,
            $voteCounts,
            $userVotes,
            $commentsCount,
            $likesCount,
            $userLikes,
            $followingIds,
            $repostCounts,
            $myReposts
        ) {
            // ── Mahsulot ma'lumotlari ──────────────────────────────────────────
            if ($post->product_id) {
                $productType = $post->product_type ?: 'book';

                $productObj = match ($productType) {
                    'book'       => $books->get($post->product_id),
                    'stationery' => $stationeries->get($post->product_id),
                    'order'      => $orders->get($post->product_id),
                    default      => null,
                };

                $post->product = $this->formatProduct($productObj, $user, $productType);
                $post->product_type = $productType;
            }

            // ── Theme ma'lumotlari ─────────────────────────────────────────────
            $post->theme = $post->theme ? [
                'id'       => $post->theme->id,
                'name'     => $post->theme->name,
                'firework' => $post->theme->firework,
                'slug'     => $post->theme->slug,
            ] : null;

            // Ovozlar
            $totalVotes = 0;
            $post->votes->each(function ($vote) use (&$totalVotes, $voteCounts, $userVotes) {
                $vote->vote_count  = $voteCounts[$vote->id] ?? 0;
                $vote->voted_by_me = $userVotes->has($vote->id);
                $totalVotes += $vote->vote_count;
            });

            $post->votes_count         = $totalVotes;
            $post->comments_count      = $commentsCount[$post->id] ?? 0;
            $post->likes_count         = $likesCount[$post->id] ?? 0;
            $post->liked_by_me         = $userLikes->has($post->id);
            $post->is_following_author = in_array($post->user_id, $followingIds);
            $postKey = $post->user_id . '_' . $post->product_id . '_' . md5($post->text);
            $post->reposted_by_me      = in_array($postKey, $myReposts);
            $post->is_repost           = $post->repost ?? false;
            $post->reposts_count       = $post->repost ? 0 : ($repostCounts[$post->user_id] ?? 0);
            $post->is_edited           = (int) ($post->edit_count ?? 0) > 0 || !empty($post->edited_at);
            $post->edited_at           = optional($post->edited_at)?->toIso8601String();
            $post->edit_count          = (int) ($post->edit_count ?? 0);
            $viewerCanModerate         = $user ? $user->canModerateCommunity() : false;
            $lastEditedById            = (int) ($post->last_edited_by_id ?? 0);
            $editedByOwner             = $lastEditedById > 0 && $lastEditedById === (int) $post->user_id;
            $editedByModeratorOrAdmin  = $post->lastEditor?->canModerateCommunity() ?? false;
            $post->show_edited_badge   = $post->is_edited && (
                $viewerCanModerate ||
                ($user && (int) $user->id === (int) $post->user_id && $editedByOwner)
            );
            $post->edited_by_staff     = $post->is_edited && $editedByModeratorOrAdmin;
            $post->warning_meta        = $user
                ? $this->moderationService->warningMetaForPost((int) $post->id, (int) $user->id, (int) $post->user_id)
                : null;

            return $post;
        });
    }

    /**
     * with() ro'yxati
     */
    private function postWith(): array
    {
        return [
            'user:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
            'originalAuthor:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
            'lastEditor:id,position',
            'images',
            'votes',
            'theme:id,name,firework,slug',
            'activeWarning:id,post_id,user_id,note,is_active,created_at',
        ];
    }

    private function applyWarningVisibility($query, $user = null)
    {
        if ($user && $user->canModerateCommunity()) {
            return $query;
        }

        return $query->where(function ($visibilityQuery) use ($user) {
            $visibilityQuery->whereDoesntHave('activeWarning');

            if ($user) {
                $visibilityQuery->orWhere('user_id', $user->id);
            }
        });
    }

    private function ensureCanModerate(User $user)
    {
        if (!$user->canModerateCommunity()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu amal uchun ruxsat yetarli emas',
            ], 403);
        }

        return null;
    }

    private function ensureCanAdministrate(User $user)
    {
        if (!$user->isAdministrator()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bu amal faqat administratorlar uchun',
            ], 403);
        }

        return null;
    }

    private function resolvePostForModeration(int $postId): BookClub
    {
        return BookClub::with('user')->findOrFail($postId);
    }

    private function blockedWriteResponse(User $user)
    {
        $payload = $this->moderationService->blockPayloadForUser($user);

        return response()->json([
            'status' => 'error',
            'error_code' => 'book_club_user_blocked',
            'message' => $payload['message'],
            'data' => $payload,
        ], 423);
    }

    private function ensureCanWrite(User $user)
    {
        if ($this->moderationService->isUserBlockedFromWriting((int) $user->id)) {
            return $this->blockedWriteResponse($user);
        }

        return null;
    }

    private function ensureSharedOrderLink(Sold $order, int $userId): SharedCart
    {
        $existing = SharedCart::where('order_id', $order->id)
            ->where('source', 'order')
            ->latest()
            ->first();

        if ($existing && !$existing->is_expired && !empty($existing->items)) {
            return $existing;
        }

        $items = [];

        foreach (($order->items ?? []) as $orderItem) {
            if (($orderItem['type'] ?? null) === 'gift') {
                continue;
            }

            $productId = $orderItem['item_id'] ?? null;
            $productType = $orderItem['type'] ?? 'book';
            $variantId = $orderItem['variant_id'] ?? null;

            $product = $productType === 'book'
                ? Books::find($productId)
                : Stationery::find($productId);

            $variant = $variantId ? StationeryVariant::find($variantId) : null;
            $currentStock = $variant
                ? ($variant->stock ?? 0)
                : ($product?->count ?? $product?->stock ?? 0);

            $items[] = [
                'product_id' => $productId,
                'product_type' => $productType,
                'variant_id' => $variantId,
                'name' => $orderItem['name'] ?? null,
                'author' => $orderItem['author'] ?? null,
                'material' => $orderItem['material'] ?? null,
                'color_name' => $orderItem['color_name'] ?? null,
                'image' => $orderItem['cover'] ?? null,
                'price' => $orderItem['item_price'] ?? 0,
                'quantity' => $orderItem['count_item'] ?? 1,
                'seller_id' => $orderItem['seller_id'] ?? null,
                'seller_name' => $orderItem['seller_name'] ?? null,
                'current_stock' => $currentStock,
            ];
        }

        return SharedCart::create([
            'user_id' => $userId,
            'slug' => Str::random(12),
            'items' => $items,
            'source' => 'order',
            'order_id' => $order->id,
        ]);
    }

    /**
     * Asosiy feed
     */
    public function index(Request $request)
    {
        try {
            $user    = Auth::guard('user')->user();
            $perPage = $request->input('per_page', 15);

            $followingIds       = $user ? $user->followings()->pluck('users.id')->toArray() : [];
            $followingIdsString = implode(',', array_merge($followingIds, [0]));

            $paginatedPosts = BookClub::with($this->postWith())
                ->where('is_deleted', false)
                ->where('repost', false)
                ->tap(fn ($query) => $this->applyWarningVisibility($query, $user))
                ->orderByRaw("CASE WHEN user_id IN ($followingIdsString) THEN 1 ELSE 0 END DESC")
                ->orderBy('updated_at', 'DESC')
                ->paginate($perPage);

            $formattedPosts = $this->attachMetaToPosts(collect($paginatedPosts->items()), $user);

            return response()->json([
                'status' => 'success',
                'data'   => $formattedPosts,
                'meta'   => [
                    'current_page' => $paginatedPosts->currentPage(),
                    'last_page'    => $paginatedPosts->lastPage(),
                    'total'        => $paginatedPosts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function postsByTheme(Request $request, string $slug)
    {
        try {
            $user = Auth::guard('user')->user();
            $perPage = (int) $request->input('per_page', 20);

            $theme = \App\Models\BookClubTheme::where('slug', $slug)
                ->orWhere('name', $slug)
                ->first();

            if (!$theme) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mavzu topilmadi',
                ], 404);
            }

            $paginatedPosts = BookClub::with($this->postWith())
                ->where('theme_id', $theme->id)
                ->where('is_deleted', false)
                ->tap(fn ($query) => $this->applyWarningVisibility($query, $user))
                ->orderBy('updated_at', 'DESC')
                ->paginate($perPage);

            $formattedPosts = $this->attachMetaToPosts(collect($paginatedPosts->items()), $user);

            return response()->json([
                'status' => 'success',
                'theme' => [
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'firework' => $theme->firework,
                ],
                'data' => $formattedPosts,
                'meta' => [
                    'current_page' => $paginatedPosts->currentPage(),
                    'last_page' => $paginatedPosts->lastPage(),
                    'total' => $paginatedPosts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Foydalanuvchi profili
     */
    public function get_profile(Request $request)
    {
        try {
            $me           = Auth::guard('user')->user();
            $targetUserId = $request->input('user_id') ?? ($me ? $me->id : null);

            if (!$targetUserId) {
                return response()->json(['status' => 'error', 'message' => 'User ID required'], 400);
            }

            $user = User::withCount(['followers', 'followings'])->find($targetUserId);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $allPosts = BookClub::with($this->postWith())
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->tap(fn ($query) => $this->applyWarningVisibility($query, $me && $me->id === $user->id ? $me : null))
                ->orderBy('created_at', 'DESC')
                ->get();

            $formattedOriginal = $this->attachMetaToPosts($allPosts->where('repost', false), $me);
            $formattedReposts  = $this->attachMetaToPosts($allPosts->where('repost', true), $me);

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'user' => [
                        'id'              => $user->id,
                        'name'            => $user->name,
                        'lastname'        => $user->lastname,
                        'position'        => $user->position,
                        'avatar'          => $user->avatar,
                        'followers_count' => $user->followers_count,
                        'following_count' => $user->followings_count,
                        'posts_count'     => $allPosts->count(),
                        'is_following'    => $me ? $me->followings()->where('following_id', $user->id)->exists() : false,
                        'is_me'           => $me ? ($me->id == $user->id) : false,
                        'isVerified'      => $user->isVerified,
                        'isSupport'       => $user->isSupport,
                        'bio'             => $user->bio,
                        'role_emoji'      => $user->role_emoji,
                        'role_title'      => $user->role_title,
                        'role_place'      => $user->role_place,
                    ],
                    'posts'   => $formattedOriginal,
                    'reposts' => $formattedReposts,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mahsulotga tegishli postlar
     */
    public function getProductPosts(Request $request, string $productId, string $type)
    {
        try {
            $user = Auth::guard('user')->user();

            $typeMap = ['book' => 'book', 'stationery' => 'stationery', 'order' => 'order'];

            if (!isset($typeMap[$type])) {
                return response()->json(['status' => 'error', 'message' => 'Noto\'g\'ri product type'], 400);
            }

            $posts = BookClub::with($this->postWith())
                ->where('product_id', $productId)
                ->where('product_type', $typeMap[$type])
                ->where('is_deleted', false)
                ->tap(fn ($query) => $this->applyWarningVisibility($query, $user))
                ->orderBy('created_at', 'DESC')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $this->attachMetaToPosts($posts, $user),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Post yaratish — book, stationery, order, yoki product_id=null
     */
    public function new_post(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }
            if ($blocked = $this->ensureCanWrite($user)) {
                return $blocked;
            }

            return DB::transaction(function () use ($request, $user) {

                // ── Product type tekshiruvi ────────────────────────────────────
                $productType = null;
                $productId   = null;

                if ($request->filled('product_type') && $request->filled('product_id')) {
                    $productType = match ($request->input('product_type')) {
                        'book'       => 'book',
                        'stationery' => 'stationery',
                        'order'      => 'order',
                        default      => null,
                    };

                    if ($productType === null) {
                        return response()->json(['status' => 'error', 'message' => 'Noto\'g\'ri product type'], 400);
                    }

                    // Order validatsiya — foydalanuvchiga tegishli bo'lishi kerak
                    if ($productType === 'order') {
                        $order = Sold::where('id', $request->input('product_id'))
                            ->where('user_id', $user->id)
                            ->first();

                        if (!$order) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => 'Bu buyurtma sizga tegishli emas yoki topilmadi',
                            ], 403);
                        }

                        $this->ensureSharedOrderLink($order, $user->id);
                    }

                    $productId = $request->input('product_id');
                }

                // ── Theme ─────────────────────────────────────────────────────
                $themeId = null;

                if ($request->boolean('new_theme') && $request->filled('theme_name')) {
                    $name  = trim($request->input('theme_name'));
                    $slug  = \Illuminate\Support\Str::slug($name);
                    $theme = \App\Models\BookClubTheme::firstOrCreate(
                        ['slug' => $slug],
                        ['user_id' => $user->id, 'name' => $name, 'status' => 1]
                    );
                    $themeId = $theme->id;
                } elseif ($request->filled('theme_id')) {
                    $themeId = $request->input('theme_id');
                }

                // ── Post ──────────────────────────────────────────────────────
                $bookClub = BookClub::create([
                    'user_id'      => $user->id,
                    'text'         => $request->input('post_text', ''),
                    'product_id'   => $productId,
                    'product_type' => $productType,
                    'theme_id'     => $themeId,
                ]);

                // ── Rasmlar ───────────────────────────────────────────────────
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $img) {
                        BookClubImages::create([
                            'user_id' => $user->id,
                            'post_id' => $bookClub->id,
                            'image'   => $img->store('book_club', 'public'),
                            'status'  => 1,
                        ]);
                    }
                }

                // ── Ovoz variantlari ──────────────────────────────────────────
                $voteOptionsRaw = $request->input('vote_options');
                if ($voteOptionsRaw) {
                    $options = is_string($voteOptionsRaw) ? json_decode($voteOptionsRaw, true) : $voteOptionsRaw;
                    if (is_array($options)) {
                        foreach ($options as $optionText) {
                            if (!empty($optionText)) {
                                BookClubVotes::create([
                                    'user_id'     => $user->id,
                                    'post_id'     => $bookClub->id,
                                    'option_text' => $optionText,
                                ]);
                            }
                        }
                    }
                }

                $this->notifyFollowers($user, $bookClub->id);

                return response()->json(['status' => 'success', 'post_id' => $bookClub->id], 201);
            });
        } catch (\Exception $e) {
            Log::error("New Post Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * new_book_post → new_post ga yo'naltiriladi (eski endpoint uchun compatibility)
     */
    public function new_book_post(Request $request)
    {
        return $this->new_post($request);
    }

    /**
     * Postni yangilash — create bilan bir xil imkoniyatlar
     * Yangilanishi mumkin: matn, rasmlar, voting, theme, product (book/stationery/order)
     */
    public function update_post(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
            if ($blocked = $this->ensureCanWrite($user)) {
                return $blocked;
            }

            return DB::transaction(function () use ($request, $user) {
                $post = BookClub::where('id', $request->post_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                if ($post->is_deleted) {
                    return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
                }

                // ── Matn ──────────────────────────────────────────────────────
                $updateData = ['text' => $request->input('post_text', $post->text)];

                // ── Theme ─────────────────────────────────────────────────────
                if ($request->boolean('clear_theme')) {
                    $updateData['theme_id'] = null;
                } elseif ($request->boolean('new_theme') && $request->filled('theme_name')) {
                    $name  = trim($request->input('theme_name'));
                    $slug  = \Illuminate\Support\Str::slug($name);
                    $theme = \App\Models\BookClubTheme::firstOrCreate(
                        ['slug' => $slug],
                        ['user_id' => $user->id, 'name' => $name, 'status' => 1]
                    );
                    $updateData['theme_id'] = $theme->id;
                } elseif ($request->filled('theme_id')) {
                    $updateData['theme_id'] = $request->input('theme_id');
                }

                // ── Product (book / stationery / order) ───────────────────────
                if ($request->boolean('clear_product')) {
                    $updateData['product_id']   = null;
                    $updateData['product_type'] = null;
                } elseif ($request->filled('product_type') && $request->filled('product_id')) {
                    $productType = match ($request->input('product_type')) {
                        'book'       => 'book',
                        'stationery' => 'stationery',
                        'order'      => 'order',
                        default      => null,
                    };

                    if ($productType !== null) {
                        if ($productType === 'order') {
                            $order = Sold::where('id', $request->input('product_id'))
                                ->where('user_id', $user->id)
                                ->first();
                            if (!$order) {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => 'Bu buyurtma sizga tegishli emas',
                                ], 403);
                            }

                            $this->ensureSharedOrderLink($order, $user->id);
                        }
                        $updateData['product_id']   = $request->input('product_id');
                        $updateData['product_type'] = $productType;
                    }
                }

                $post->update($updateData);
                $post->forceFill([
                    'edited_at' => now(),
                    'edit_count' => (int) ($post->edit_count ?? 0) + 1,
                    'last_edited_by_id' => $user->id,
                ])->save();

                // ── Rasmlarni o'chirish ────────────────────────────────────────
                $deletedIds = json_decode($request->input('deleted_image_ids', '[]'), true);
                if (!empty($deletedIds)) {
                    $images = BookClubImages::whereIn('id', $deletedIds)
                        ->where('post_id', $post->id)
                        ->get();
                    foreach ($images as $img) {
                        Storage::disk('public')->delete($img->image);
                        $img->delete();
                    }
                }

                // ── Yangi rasmlar ─────────────────────────────────────────────
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        BookClubImages::create([
                            'user_id' => $user->id,
                            'post_id' => $post->id,
                            'image'   => $file->store('book_club', 'public'),
                            'status'  => 1,
                        ]);
                    }
                }

                // ── Mavjud voting o'zgartirish ────────────────────────────────
                $updatedVotes = json_decode($request->input('updated_votes', '[]'), true);
                if (!empty($updatedVotes)) {
                    foreach ($updatedVotes as $vData) {
                        BookClubVotes::where('id', $vData['id'] ?? 0)
                            ->where('post_id', $post->id)
                            ->update(['option_text' => $vData['text'] ?? '']);
                    }
                }

                // ── Yangi voting variantlari ──────────────────────────────────
                $newVotes = json_decode($request->input('new_vote_options', '[]'), true);
                if (!empty($newVotes)) {
                    foreach ($newVotes as $text) {
                        if (!empty(trim($text))) {
                            BookClubVotes::create([
                                'user_id'     => $user->id,
                                'post_id'     => $post->id,
                                'option_text' => trim($text),
                            ]);
                        }
                    }
                }

                return response()->json(['status' => 'success', 'message' => 'Post yangilandi']);
            });
        } catch (\Exception $e) {
            Log::error('Update Post Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function moderateWarn(Request $request, int $postId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        if ($denied = $this->ensureCanModerate($user)) {
            return $denied;
        }

        $data = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $post = $this->resolvePostForModeration($postId);

        if ($post->user_id === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'O\'z postingizga warning bera olmaysiz',
            ], 422);
        }

        \App\Models\BookClubWarning::updateOrCreate(
            ['post_id' => $post->id],
            [
                'user_id' => $post->user_id,
                'admin_id' => $user->id,
                'note' => trim((string) $data['note']),
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Post ogohlantirildi',
            'data' => [
                'warnings_count' => $this->moderationService->activeWarningCountForUser((int) $post->user_id),
                'block_threshold' => BookClubModerationService::BLOCK_THRESHOLD,
            ],
        ]);
    }

    public function moderateEdit(Request $request, int $postId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        if ($denied = $this->ensureCanAdministrate($user)) {
            return $denied;
        }

        $data = $request->validate([
            'text' => 'required|string|max:20000',
        ]);

        $post = $this->resolvePostForModeration($postId);
        if ($post->is_deleted) {
            return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
        }

        $post->update([
            'text' => trim((string) $data['text']),
            'edited_at' => now(),
            'edit_count' => (int) ($post->edit_count ?? 0) + 1,
            'last_edited_by_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Post matni yangilandi',
            'data' => [
                'text' => $post->text,
            ],
        ]);
    }

    public function moderateBanUser(Request $request, int $postId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        if ($denied = $this->ensureCanAdministrate($user)) {
            return $denied;
        }

        $data = $request->validate([
            'block_period' => 'required|in:10_days,1_month,1_year,3_years,forever',
            'reason' => 'required|string|max:5000',
        ]);

        $post = $this->resolvePostForModeration($postId);
        $targetUser = $post->user;

        if (!$targetUser) {
            return response()->json(['status' => 'error', 'message' => 'Foydalanuvchi topilmadi'], 404);
        }

        if ($targetUser->id === $user->id) {
            return response()->json(['status' => 'error', 'message' => 'O\'zingizni bloklay olmaysiz'], 422);
        }

        $blockedUntil = match ($data['block_period']) {
            '10_days' => now()->addDays(10),
            '1_month' => now()->addMonth(),
            '1_year' => now()->addYear(),
            '3_years' => now()->addYears(3),
            'forever' => null,
        };

        DB::transaction(function () use ($targetUser, $user, $data, $blockedUntil) {
            $targetUser->forceFill([
                'status' => 'blocked',
                'blocked_until' => $blockedUntil,
                'blocked_at' => now(),
                'block_reason' => trim((string) $data['reason']),
                'blocked_by_admin_id' => $user->id,
            ])->save();

            $targetUser->tokens()->delete();

            DB::table('connected_devices')
                ->where('user_id', $targetUser->id)
                ->where('user_type', 'user')
                ->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Foydalanuvchi bloklandi',
        ]);
    }


    /**
     * Like / Unlike
     */
    public function like(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $post = BookClub::findOrFail($request->post_id);

            if ($post->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
            }

            $like = BookClubLikes::where('post_id', $post->id)->where('user_id', $user->id)->first();

            if (!$like) {
                BookClubLikes::create(['post_id' => $post->id, 'user_id' => $user->id]);
                $this->sendNotification($post->user_id, $user, 'like', $post->id);
                $status = 'liked';
            } else {
                $like->delete();

                $groupKey     = "like_post_{$post->id}";
                $notification = BookClubNotification::where('user_id', $post->user_id)
                    ->where('group_key', $groupKey)
                    ->where('is_read', 0)
                    ->first();

                if ($notification) {
                    NotificationHelper::removeActor($notification, (int) $user->id);
                }
                $status = 'unliked';
            }

            return response()->json(['status' => 'success', 'like_status' => $status]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Ovoz berish
     */
    public function vote($optionId, Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $option = BookClubVotes::findOrFail($optionId);
            $post   = BookClub::findOrFail($option->post_id);

            if ($post->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
            }

            $alreadyVoted = DB::table('book_club_voted_users')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->exists();

            if ($alreadyVoted) {
                return response()->json(['status' => 'error', 'message' => 'Siz ushbu so\'rovnomada qatnashib bo\'lgansiz'], 400);
            }

            DB::table('book_club_voted_users')->insert([
                'user_id'    => $user->id,
                'post_id'    => $post->id,
                'option_id'  => $optionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->sendNotification($post->user_id, $user, 'vote', $post->id);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Follow / Unfollow
     */
    public function followUser(Request $request)
    {
        try {
            $me     = Auth::guard('user')->user();
            $target = User::findOrFail($request->user_id);

            if ($me->id === $target->id) {
                return response()->json(['status' => 'error', 'message' => 'O\'zingizga obuna bo\'lolmaysiz'], 400);
            }

            if ($me->followings()->where('following_id', $target->id)->exists()) {
                $me->followings()->detach($target->id);
                BookClubNotification::where('user_id', $target->id)
                    ->where('group_key', "follow_user_{$me->id}")
                    ->where('is_read', 0)
                    ->delete();
                $status = 'unfollowed';
            } else {
                $me->followings()->attach($target->id);
                $this->sendNotification($target->id, $me, 'follow');
                $status = 'followed';
            }

            return response()->json([
                'status'          => 'success',
                'follow_status'   => $status,
                'followers_count' => $target->followers()->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Repost
     */
    public function repost(Request $request, $id)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }
            if ($blocked = $this->ensureCanWrite($user)) {
                return $blocked;
            }

            $original = BookClub::with(['images', 'votes'])->findOrFail($id);

            if ($original->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
            }

            return DB::transaction(function () use ($user, $original) {
                $newPost = BookClub::create([
                    'user_id'          => $user->id,
                    'text'             => $original->text,
                    'product_id'       => $original->product_id,
                    'product_type'     => $original->product_type,
                    'theme_id'         => $original->theme_id,
                    'repost'           => true,
                    'reposted_user_id' => $original->user_id,
                ]);

                foreach ($original->images as $img) {
                    BookClubImages::create([
                        'user_id' => $user->id,
                        'post_id' => $newPost->id,
                        'image'   => $img->image,
                        'status'  => $img->status,
                    ]);
                }

                foreach ($original->votes as $vote) {
                    BookClubVotes::create([
                        'user_id'     => $user->id,
                        'post_id'     => $newPost->id,
                        'option_text' => $vote->option_text,
                    ]);
                }

                $this->sendNotification($original->user_id, $user, 'repost', $original->id);
                $this->notifyFollowers($user, $newPost->id);

                return response()->json(['status' => 'success', 'post_id' => $newPost->id]);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Postni o'chirish (soft delete)
     */
    public function deleteClubPost(Request $request, $id)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $post = BookClub::where('user_id', $user->id)->where('id', $id)->first();

            if (!$post || $post->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post topilmadi yoki allaqachon o\'chirilgan'], 404);
            }

            $post->update(['is_deleted' => true]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Yordamchi metodlar ───────────────────────────────────────────────────────

    private function notifyFollowers($user, $postId)
    {
        $followers = DB::table('user_follows')
            ->where('following_id', $user->id)
            ->pluck('follower_id');

        foreach ($followers as $fId) {
            $this->sendNotification($fId, $user, 'new_post', $postId);
        }
    }

    private function sendNotification($receiverId, $sender, $type, $postId = null)
    {
        NotificationHelper::send($receiverId, $sender, $type, $postId);
    }

    public function getNotifications(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $notifications = BookClubNotification::where('user_id', $user->id)
                ->orderBy('updated_at', 'DESC')
                ->limit(30)
                ->get();

            $actorIds = $notifications
                ->flatMap(function ($notification) {
                    $data = $notification->data ?? [];
                    return collect($data['user_ids'] ?? [])->push($data['last_user_id'] ?? null);
                })
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $usersById = User::whereIn('id', $actorIds)->get()->keyBy('id');

            $notifications = $notifications->map(function ($n) use ($user, $usersById) {
                    $data  = $n->data;
                    $formatted = $this->notificationTextService->format($n, $user->locale ?? 'uz');
                    $actors = collect($data['actors'] ?? []);

                    if ($actors->isEmpty() && !empty($data['user_ids'])) {
                        $actors = collect($data['user_ids'])
                            ->map(fn ($id) => (int) $id)
                            ->unique()
                            ->take(8)
                            ->map(function ($id) use ($usersById, $n) {
                                $actorUser = $usersById->get($id);
                                return [
                                    'user_id' => $id,
                                    'name' => $actorUser
                                        ? trim(($actorUser->name ?? '') . ' ' . ($actorUser->lastname ?? ''))
                                        : null,
                                    'avatar' => $actorUser?->avatar,
                                    'acted_at' => optional($n->updated_at)?->toIso8601String(),
                                ];
                            })
                            ->values();
                    }

                    if ($actors->isEmpty() && !empty($data['last_user_id'])) {
                        $actors = collect([[
                            'user_id' => $data['last_user_id'],
                            'name' => $data['last_user_name'] ?? null,
                            'avatar' => $data['last_user_avatar'] ?? null,
                            'acted_at' => optional($n->updated_at)?->toIso8601String(),
                        ]]);
                    }

                    return [
                        'id'         => $n->id,
                        'type'       => $n->type,
                        'post_id'    => $n->post_id,
                        'sender_user_id' => $data['last_user_id'] ?? null,
                        'sender_name' => $data['last_user_name'] ?? null,
                        'title'      => $formatted['title'],
                        'text'       => $formatted['body'],
                        'group_count'=> $formatted['group_count'],
                        'avatar'     => $data['last_user_avatar'] ?? null,
                        'actors'     => $actors->take(8)->map(function ($actor) use ($user) {
                            $actedAt = !empty($actor['acted_at'])
                                ? \Illuminate\Support\Carbon::parse($actor['acted_at'])
                                : null;

                            return [
                                'user_id' => $actor['user_id'] ?? null,
                                'name' => $actor['name'] ?? null,
                                'avatar' => $actor['avatar'] ?? null,
                                'acted_at' => $actedAt?->toIso8601String(),
                                'acted_at_human' => $actedAt?->locale($user->locale ?? 'uz')->diffForHumans(),
                            ];
                        })->values(),
                        'is_read'    => $n->is_read,
                        'created_at' => optional($n->updated_at)
                            ?->locale($user->locale ?? 'uz')
                            ->diffForHumans(),
                        'created_at_iso' => optional($n->updated_at)?->toIso8601String(),
                    ];
                });

            return response()->json(['status' => 'success', 'data' => $notifications]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function readNotifications(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            BookClubNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function suggestedUsers(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $limit = min(max((int) $request->query('limit', 8), 1), 12);
        $followingIds = $user->followings()->pluck('users.id');
        $excludedIds = $followingIds->push($user->id)->unique()->values();

        $mutualSub = DB::table('user_follows')
            ->select('following_id', DB::raw('COUNT(*) as mutual_count'))
            ->whereIn('follower_id', $followingIds->isEmpty() ? [0] : $followingIds->all())
            ->groupBy('following_id');

        $suggested = User::query()
            ->leftJoinSub($mutualSub, 'mutuals', function ($join) {
                $join->on('mutuals.following_id', '=', 'users.id');
            })
            ->whereNotIn('users.id', $excludedIds->all())
            ->where(function ($q) {
                $q->whereNotNull('users.avatar')
                    ->orWhereNotNull('users.bio')
                    ->orWhereNotNull('users.position');
            })
            ->withCount([
                'followers',
                'followings',
            ])
            ->select('users.*', DB::raw('COALESCE(mutuals.mutual_count, 0) as mutual_count'))
            ->orderByDesc('mutual_count')
            ->orderByDesc('users.last_seen_at')
            ->orderByDesc('users.id')
            ->limit($limit)
            ->get()
            ->map(function ($suggestedUser) use ($user) {
                $postsCount = BookClub::where('user_id', $suggestedUser->id)
                    ->where('is_deleted', false)
                    ->count();

                return [
                    'id' => $suggestedUser->id,
                    'name' => $suggestedUser->name,
                    'lastname' => $suggestedUser->lastname,
                    'position' => $suggestedUser->position,
                    'avatar' => $suggestedUser->avatar,
                    'bio' => $suggestedUser->bio,
                    'isVerified' => (bool) $suggestedUser->isVerified,
                    'isSupport' => (bool) $suggestedUser->isSupport,
                    'role_emoji' => $suggestedUser->role_emoji,
                    'role_title' => $suggestedUser->role_title,
                    'role_place' => $suggestedUser->role_place,
                    'followers_count' => (int) $suggestedUser->followers_count,
                    'followings_count' => (int) $suggestedUser->followings_count,
                    'posts_count' => (int) $postsCount,
                    'mutual_count' => (int) ($suggestedUser->mutual_count ?? 0),
                    'is_following' => $user->isFollowing($suggestedUser->id),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $suggested,
        ]);
    }
}
