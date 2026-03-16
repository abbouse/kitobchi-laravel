<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, Books, Stationery, BookClub, BookClubImages, BookClubLikes, BookClubVotes, BookClubComment, FavouriteProducts, BookClubNotification};
use App\Jobs\SendBookClubPushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage, Auth};

class BookClubController extends Controller
{
    /**
     * Mahsulotni API uchun formatlash
     */
    private function formatProduct($product, $user = null)
    {
        if (!$product) return null;

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
        ->map(function($r) {
            return $r->reposted_user_id . '_' . $r->product_id . '_' . md5($r->text);
        })->toArray() : [];
        // Mahsulotlar uchun eager loading
        $bookIds = $posts->where('product_type', 'book')->pluck('product_id')->filter()->unique();
        $statIds = $posts->where('product_type', 'stationery')->pluck('product_id')->filter()->unique();

        $books = Books::with(['category', 'tags', 'seller'])->whereIn('id', $bookIds)->get()->keyBy('id');
        $stationeries = Stationery::with(['category', 'tags', 'seller'])->whereIn('id', $statIds)->get()->keyBy('id');

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
            $voteCounts,
            $userVotes,
            $commentsCount,
            $likesCount,
            $userLikes,
            $followingIds,
            $repostCounts,
            $myReposts
        ) {
            // Mahsulot ma'lumotlari
            if ($post->product_id) {
                $productObj = ($post->product_type === 'book')
                    ? $books->get($post->product_id)
                    : $stationeries->get($post->product_id);

                $post->product = $this->formatProduct($productObj, $user);
            }

            // Ovozlar
            $totalVotes = 0;
            $post->votes->each(function ($vote) use (&$totalVotes, $voteCounts, $userVotes) {
                $vote->vote_count = $voteCounts[$vote->id] ?? 0;
                $vote->voted_by_me = $userVotes->has($vote->id);
                $totalVotes += $vote->vote_count;
            });

            $post->votes_count     = $totalVotes;
            $post->comments_count  = $commentsCount[$post->id] ?? 0;
            $post->likes_count     = $likesCount[$post->id] ?? 0;
            $post->liked_by_me     = $userLikes->has($post->id);
            $post->is_following_author = in_array($post->user_id, $followingIds);
            $postKey = $post->user_id . '_' . $post->product_id . '_' . md5($post->text);
            $post->reposted_by_me = in_array($postKey, $myReposts);
            $post->is_repost       = $post->repost ?? false;
            $post->reposts_count   = $post->repost ? 0 : ($repostCounts[$post->user_id] ?? 0);

            return $post;
        });
    }

    /**
     * Asosiy feed - faqat original postlar
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $perPage = $request->input('per_page', 15);

            $followingIds = $user ? $user->followings()->pluck('users.id')->toArray() : [];
            $followingIdsString = implode(',', array_merge($followingIds, [0]));

            $query = BookClub::with([
                'user:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
                'originalAuthor:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
                'images',
                'votes'
            ])
                ->where('is_deleted', false)
                ->where('repost', false);

            $paginatedPosts = $query->orderByRaw("CASE WHEN user_id IN ($followingIdsString) THEN 1 ELSE 0 END DESC")
                ->orderBy('updated_at', 'DESC')
                ->paginate($perPage);

            $formattedPosts = $this->attachMetaToPosts(collect($paginatedPosts->items()), $user);

            return response()->json([
                'status' => 'success',
                'data'   => $formattedPosts,
                'meta'   => [
                    'current_page' => $paginatedPosts->currentPage(),
                    'last_page'    => $paginatedPosts->lastPage(),
                    'total'        => $paginatedPosts->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Foydalanuvchi profili - original postlar va repostlar alohida
     */
    public function get_profile(Request $request)
    {
        try {
            $me = Auth::guard('user')->user();
            $targetUserId = $request->input('user_id') ?? ($me ? $me->id : null);

            if (!$targetUserId) {
                return response()->json(['status' => 'error', 'message' => 'User ID required'], 400);
            }

            $user = User::withCount(['followers', 'followings'])->find($targetUserId);

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $allPosts = BookClub::with([
                'user:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
                'originalAuthor:id,name,lastname,position,avatar,isVerified,isSupport,bio,role_emoji,role_title,role_place',
                'images',
                'votes'
            ])
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->orderBy('created_at', 'DESC')
                ->get();

            $originalPosts = $allPosts->where('repost', false);
            $reposts = $allPosts->where('repost', true);

            $formattedOriginal = $this->attachMetaToPosts($originalPosts, $me);
            $formattedReposts = $this->attachMetaToPosts($reposts, $me);

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
                    'reposts' => $formattedReposts
                ]
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

            $typeMap = [
                'book'       => 'book',
                'stationery' => 'stationery'
            ];

            if (!isset($typeMap[$type])) {
                return response()->json(['status' => 'error', 'message' => 'Noto\'g\'ri product type'], 400);
            }

            $modelClass = $typeMap[$type];

            $posts = BookClub::with([
                'user:id,name,lastname,position,avatar,isVerified,isSupport,role_emoji,role_title,role_place',
                'images',
                'votes'
            ])
                ->where('product_id', $productId)
                ->where('product_type', $modelClass)
                ->where('is_deleted', false)
                ->orderBy('created_at', 'DESC')
                ->get();

            $formattedPosts = $this->attachMetaToPosts($posts, $user);

            return response()->json([
                'status' => 'success',
                'data'   => $formattedPosts
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Oddiy (matnli) post yaratish
     */
    public function new_post(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            return DB::transaction(function () use ($request, $user) {
                $bookClub = BookClub::create([
                    'user_id' => $user->id,
                    'text'    => $request->post_text ?? '',
                ]);

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $img) {
                        BookClubImages::create([
                            'user_id' => $user->id,
                            'post_id' => $bookClub->id,
                            'image'   => $img->store('book_club', 'public'),
                            'status'  => 1
                        ]);
                    }
                }

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

                return response()->json([
                    'status'  => 'success',
                    'post_id' => $bookClub->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("New Post Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mahsulot bilan bog'langan post yaratish
     */
    public function new_book_post(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            return DB::transaction(function () use ($request, $user) {
                $productType = null;
                if ($request->product_type === 'book') {
                    $productType = 'book';
                } elseif ($request->product_type === 'stationery') {
                    $productType = 'stationery';
                }

                if (!$productType) {
                    throw new \Exception('Mahsulot turi noto\'g\'ri');
                }

                $bookClub = BookClub::create([
                    'user_id'      => $user->id,
                    'text'         => $request->post_text ?? '',
                    'product_id'   => $request->product_id,
                    'product_type' => $productType,
                ]);

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $img) {
                        BookClubImages::create([
                            'user_id' => $user->id,
                            'post_id' => $bookClub->id,
                            'image'   => $img->store('book_club', 'public'),
                            'status'  => 1
                        ]);
                    }
                }

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

                return response()->json([
                    'status'  => 'success',
                    'post_id' => $bookClub->id,
                    'message' => 'Post muvaffaqiyatli saqlandi'
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("New Book Post Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Postni yangilash
     */
    public function update_post(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            return DB::transaction(function () use ($request, $user) {
                $post = BookClub::where('id', $request->post_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                if ($post->is_deleted) {
                    return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
                }

                $post->update(['text' => $request->post_text ?? '']);

                // Eski rasmlarni o'chirish
                $deletedIds = json_decode($request->deleted_image_ids ?? '[]', true);
                if (!empty($deletedIds)) {
                    $images = BookClubImages::whereIn('id', $deletedIds)->get();
                    foreach ($images as $img) {
                        Storage::disk('public')->delete($img->image);
                        $img->delete();
                    }
                }

                // Yangi rasmlar qo'shish
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        BookClubImages::create([
                            'user_id' => $user->id,
                            'post_id' => $post->id,
                            'image'   => $file->store('book_club', 'public'),
                            'status'  => 1
                        ]);
                    }
                }

                // Ovoz variantlarini yangilash
                $updatedVotes = json_decode($request->updated_votes ?? '[]', true);
                if (!empty($updatedVotes)) {
                    foreach ($updatedVotes as $vData) {
                        BookClubVotes::where('id', $vData['id'] ?? 0)
                            ->update(['option_text' => $vData['text'] ?? '']);
                    }
                }

                $newVotes = json_decode($request->new_vote_options ?? '[]', true);
                if (!empty($newVotes)) {
                    foreach ($newVotes as $text) {
                        if (!empty($text)) {
                            BookClubVotes::create([
                                'user_id'     => $user->id,
                                'post_id'     => $post->id,
                                'option_text' => $text,
                            ]);
                        }
                    }
                }

                return response()->json(['status' => 'success', 'message' => 'Post yangilandi']);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
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

            $like = BookClubLikes::where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$like) {
                BookClubLikes::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id
                ]);
                $this->sendNotification($post->user_id, $user, 'like', $post->id);
                $status = 'liked';
            } else {
                $like->delete();

                // Bildirishnomani yangilash yoki o'chirish
                $groupKey = "like_post_{$post->id}";
                $notification = BookClubNotification::where('user_id', $post->user_id)
                    ->where('group_key', $groupKey)
                    ->where('is_read', 0)
                    ->first();

                if ($notification) {
                    $data = $notification->data;
                    if (($data['count'] ?? 1) <= 1) {
                        $notification->delete();
                    } else {
                        $data['user_names'] = array_values(array_diff($data['user_names'] ?? [], [$user->name]));
                        $data['count'] = count($data['user_names']);

                        if ($data['count'] > 0) {
                            $data['last_user_name'] = end($data['user_names']);
                            $lastUser = User::where('name', $data['last_user_name'])->first();
                            $data['last_user_avatar'] = $lastUser?->avatar;
                            $notification->update(['data' => $data]);
                        } else {
                            $notification->delete();
                        }
                    }
                }
                $status = 'unliked';
            }

            return response()->json([
                'status'     => 'success',
                'like_status' => $status
            ]);
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
            $post = BookClub::findOrFail($option->post_id);

            if ($post->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
            }

            $alreadyVoted = DB::table('book_club_voted_users')
                ->where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->exists();

            if ($alreadyVoted) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Siz ushbu so\'rovnomada qatnashib bo\'lgansiz'
                ], 400);
            }

            DB::table('book_club_voted_users')->insert([
                'user_id'    => $user->id,
                'post_id'    => $post->id,
                'option_id'  => $optionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Foydalanuvchiga obuna bo'lish / obunani bekor qilish
     */
    public function followUser(Request $request)
    {
        try {
            $me = Auth::guard('user')->user();
            $target = User::findOrFail($request->user_id);

            if ($me->id === $target->id) {
                return response()->json(['status' => 'error', 'message' => 'O\'zingizga obuna bo\'lolmaysiz'], 400);
            }

            if ($me->followings()->where('following_id', $target->id)->exists()) {
                $me->followings()->detach($target->id);
                $status = "unfollowed";
            } else {
                $me->followings()->attach($target->id);
                $this->sendNotification($target->id, $me, 'follow');
                $status = "followed";
            }

            return response()->json([
                'status'          => 'success',
                'follow_status'   => $status,
                'followers_count' => $target->followers()->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Repost qilish
     */
    public function repost(Request $request, $id)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $original = BookClub::with(['images', 'votes'])->findOrFail($id);

            if ($original->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post o\'chirilgan'], 400);
            }

            return DB::transaction(function () use ($user, $original) {
                $newPost = BookClub::create([
                    'user_id'         => $user->id,
                    'text'            => $original->text,
                    'product_id'      => $original->product_id,
                    'product_type'    => $original->product_type,
                    'repost'          => true,
                    'reposted_user_id' => $original->user_id,
                ]);

                // Rasmlarni ko'chirish
                foreach ($original->images as $img) {
                    BookClubImages::create([
                        'user_id' => $user->id,
                        'post_id' => $newPost->id,
                        'image'   => $img->image,
                        'status'  => $img->status,
                    ]);
                }

                // Ovoz variantlarini ko'chirish
                foreach ($original->votes as $vote) {
                    BookClubVotes::create([
                        'user_id'     => $user->id,
                        'post_id'     => $newPost->id,
                        'option_text' => $vote->option_text,
                    ]);
                }

                // Original post egasiga bildirishnoma
                $this->sendNotification($original->user_id, $user, 'repost', $original->id);

                // Repost qiluvchining obunachilariga bildirishnoma
                $this->notifyFollowers($user, $newPost->id);

                return response()->json([
                    'status'  => 'success',
                    'post_id' => $newPost->id
                ]);
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

            $post = BookClub::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$post || $post->is_deleted) {
                return response()->json(['status' => 'error', 'message' => 'Post topilmadi yoki allaqachon o\'chirilgan'], 404);
            }

            $post->update(['is_deleted' => true]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────────
    // Yordamchi metodlar
    // ──────────────────────────────────────────────────────────────────────────────

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
        if ($receiverId == $sender->id) {
            return;
        }

        $groupKey = $postId ? "{$type}_post_{$postId}" : "{$type}_user_{$sender->id}";

        $notification = BookClubNotification::where('user_id', $receiverId)
            ->where('group_key', $groupKey)
            ->where('is_read', 0)
            ->first();

        if ($notification) {
            $data = $notification->data;
            if (!in_array($sender->name, $data['user_names'] ?? [])) {
                $data['count'] = ($data['count'] ?? 1) + 1;
                $data['user_names'][] = $sender->name;
                $data['last_user_name'] = $sender->name;
                $data['last_user_avatar'] = $sender->avatar;
                $notification->update(['data' => $data, 'updated_at' => now()]);
            }
        } else {
            BookClubNotification::create([
                'user_id' => $receiverId,
                'type'    => $type,
                'post_id' => $postId,
                'group_key' => $groupKey,
                'data'    => [
                    'count'           => 1,
                    'last_user_name'  => $sender->name,
                    'last_user_avatar' => $sender->avatar,
                    'user_names'      => [$sender->name]
                ]
            ]);
        }

        SendBookClubPushNotification::dispatch($notification?->id ?? 0)->delay(now()->addSeconds(2));
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
                ->get()
                ->map(function ($n) {
                    $data = $n->data;
                    $name = $data['last_user_name'] ?? 'Kimdir';
                    $extra = ($data['count'] ?? 1) - 1;

                    $text = match ($n->type) {
                        'like'    => $extra > 0 ? "$name va yana $extra kishi like bosdi" : "$name postga like bosdi",
                        'new_post'=> "$name yangi post qoldirdi",
                        'follow'  => "$name sizga obuna bo'ldi",
                        'repost'  => $extra > 0 ? "$name va yana $extra kishi repost qildi" : "$name postni repost qildi",
                        default   => "Yangi bildirishnoma"
                    };

                    return [
                        'id'         => $n->id,
                        'type'       => $n->type,
                        'post_id'    => $n->post_id,
                        'text'       => $text,
                        'avatar'     => $data['last_user_avatar'] ?? null,
                        'is_read'    => $n->is_read,
                        'created_at' => $n->created_at->diffForHumans()
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data'   => $notifications
            ]);
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
}