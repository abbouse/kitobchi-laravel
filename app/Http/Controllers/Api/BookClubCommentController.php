<?php
namespace App\Http\Controllers\Api;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BookClubComment;
use App\Models\BookClubCommentReply;
use App\Models\BookClubCommentLike;
use App\Models\BookClub;
use App\Services\BookClubModerationService;
use App\Services\BookClubContentPolicy;
use App\Services\MentionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookClubCommentController extends Controller {
    public function __construct(
        private readonly BookClubModerationService $moderationService,
        private readonly MentionService $mentionService,
        private readonly BookClubContentPolicy $contentPolicy,
    ) {}
    
    private function visibleCommentContent(BookClubComment $comment, ?User $viewer): string
    {
        if (! $comment->is_hidden_by_ai
            || ($viewer && ((int) $viewer->id === (int) $comment->user_id || $viewer->canModerateCommunity()))
        ) {
            return (string) $comment->content;
        }

        return '';
    }

    private function commentUserSelect(): array
    {
        return [
            'id',
            'name',
            'lastname',
            'username',
            'sex',
            'avatar',
            'position',
            'staff_role',
            'isVerified',
            'isSupport',
            'role_emoji',
            'role_title',
            'role_place',
        ];
    }

    private function serializeComment(BookClubComment $comment, ?User $viewer = null, array $extra = []): array
    {
        $replyTarget = $comment->replyToUser;
        $liveReplyUsername = trim((string) ($replyTarget?->username ?? ''));
        $liveReplyName = trim((string) ($replyTarget?->name ?? ''));

        return array_merge([
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id,
            'content' => $this->visibleCommentContent($comment, $viewer),
            'created_at' => optional($comment->created_at)?->toIso8601String(),
            'user' => $comment->user,
            'likes' => (int) ($comment->likes_count ?? $comment->likes?->count() ?? 0),
            'liked' => $viewer ? ($comment->liked_by_viewer ?? $comment->likes?->contains('user_id', $viewer->id) ?? false) : false,
            'replies_count' => (int) ($comment->replies_count ?? 0),
            'is_hidden' => (bool) $comment->is_hidden_by_ai,
            'ai_moderation_status' => $comment->ai_moderation_status,
            'reply_to_user' => $comment->reply_to_user_id
                ? [
                    'id' => $comment->reply_to_user_id ? (int) $comment->reply_to_user_id : null,
                    'username' => $liveReplyUsername !== '' ? $liveReplyUsername : null,
                    'display_name' => $liveReplyName !== '' ? $liveReplyName : null,
                ]
                : null,
        ], $extra);
    }

    private function viewerFollowingIds(?User $user): array
    {
        if (!$user) {
            return [0];
        }

        $ids = $user->followings()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $ids[] = 0;

        return array_values(array_unique($ids));
    }

    private function normalizeReplySort(?string $sort): string
    {
        return match ($sort) {
            'newest', 'oldest', 'interesting' => $sort,
            default => 'interesting',
        };
    }

    private function applyAiVisibility($query, ?User $viewer)
    {
        if ($viewer?->canModerateCommunity()) {
            return $query;
        }

        return $query->where(function ($visibility) use ($viewer) {
            $visibility->whereNull('book_club_comments.is_hidden_by_ai')
                ->orWhere('book_club_comments.is_hidden_by_ai', false);

            if ($viewer) {
                $visibility->orWhere('book_club_comments.user_id', $viewer->id);
            }
        });
    }

    private function rankedRepliesQuery(int $commentId, array $followingIds, int $postOwnerId, int $commentOwnerId, string $sort = 'interesting')
    {
        $followingIdsString = implode(',', array_map('intval', $followingIds ?: [0]));
        $likesCountSql = "(SELECT COUNT(*) FROM book_club_comment_likes WHERE book_club_comment_likes.comment_id = book_club_comments.id)";
        $followersCountSql = "(SELECT COUNT(*) FROM user_follows WHERE user_follows.following_id = book_club_comments.user_id)";

        $scoreSql = "
            (
                CASE WHEN book_club_comments.user_id = {$postOwnerId} THEN 120 ELSE 0 END +
                CASE WHEN book_club_comments.user_id = {$commentOwnerId} THEN 65 ELSE 0 END +
                CASE WHEN users.isSupport = 1 THEN 95 ELSE 0 END +
                CASE WHEN users.isVerified = 1 THEN 70 ELSE 0 END +
                CASE WHEN book_club_comments.user_id IN ({$followingIdsString}) THEN 85 ELSE 0 END +
                LEAST(42, ({$followersCountSql}) / 25) +
                LEAST(20, ({$likesCountSql}) * 2) +
                CASE WHEN book_club_comments.created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR) THEN 8 ELSE 0 END
            )
        ";

        $query = BookClubComment::query()
            ->where('book_club_comments.parent_id', $commentId)
            ->leftJoin('users', 'users.id', '=', 'book_club_comments.user_id')
            ->select('book_club_comments.*')
            ->selectRaw("{$likesCountSql} as likes_count")
            ->selectRaw("{$scoreSql} as smart_rank")
            ->with([
                'user' => fn ($query) => $query
                    ->select($this->commentUserSelect())
                    ->withCount('followers'),
                'replyToUser' => fn ($query) => $query->select(['id', 'name', 'username']),
            ])
            ->withCount('replies');

        return match ($this->normalizeReplySort($sort)) {
            'newest' => $query
                ->orderByDesc('book_club_comments.created_at')
                ->orderByDesc('book_club_comments.id'),
            'oldest' => $query
                ->orderBy('book_club_comments.created_at')
                ->orderBy('book_club_comments.id'),
            default => $query
                ->orderByDesc('smart_rank')
                ->orderByDesc('likes_count')
                ->orderBy('book_club_comments.created_at'),
        };
    }

    private function rankedTopLevelCommentsQuery(int $postId, array $followingIds, int $postOwnerId, string $sort = 'interesting')
    {
        $followingIdsString = implode(',', array_map('intval', $followingIds ?: [0]));
        $likesCountSql = "(SELECT COUNT(*) FROM book_club_comment_likes WHERE book_club_comment_likes.comment_id = book_club_comments.id)";
        $followersCountSql = "(SELECT COUNT(*) FROM user_follows WHERE user_follows.following_id = book_club_comments.user_id)";
        $repliesCountSql = "(SELECT COUNT(*) FROM book_club_comments child_comments WHERE child_comments.parent_id = book_club_comments.id AND COALESCE(child_comments.is_hidden_by_ai, 0) = 0)";

        $scoreSql = "
            (
                CASE WHEN book_club_comments.user_id = {$postOwnerId} THEN 85 ELSE 0 END +
                CASE WHEN users.isSupport = 1 THEN 95 ELSE 0 END +
                CASE WHEN users.isVerified = 1 THEN 70 ELSE 0 END +
                CASE WHEN book_club_comments.user_id IN ({$followingIdsString}) THEN 85 ELSE 0 END +
                LEAST(42, ({$followersCountSql}) / 25) +
                LEAST(24, ({$likesCountSql}) * 2) +
                LEAST(18, ({$repliesCountSql}) * 3) +
                CASE WHEN book_club_comments.created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR) THEN 8 ELSE 0 END
            )
        ";

        $query = BookClubComment::query()
            ->where('book_club_comments.post_id', $postId)
            ->whereNull('book_club_comments.parent_id')
            ->leftJoin('users', 'users.id', '=', 'book_club_comments.user_id')
            ->select('book_club_comments.*')
            ->selectRaw("{$likesCountSql} as likes_count")
            ->selectRaw("{$scoreSql} as smart_rank")
            ->with([
                'user' => fn ($query) => $query
                    ->select($this->commentUserSelect())
                    ->withCount('followers'),
                'replyToUser' => fn ($query) => $query->select(['id', 'name', 'username']),
                'likes',
            ])
            ->withCount('replies');

        return match ($this->normalizeReplySort($sort)) {
            'newest' => $query
                ->orderByDesc('book_club_comments.created_at')
                ->orderByDesc('book_club_comments.id'),
            'oldest' => $query
                ->orderBy('book_club_comments.created_at')
                ->orderBy('book_club_comments.id'),
            default => $query
                ->orderByDesc('smart_rank')
                ->orderByDesc('likes_count')
                ->orderByDesc('replies_count')
                ->orderByDesc('book_club_comments.created_at'),
        };
    }

    private function replyPreviewBundle(
        BookClubComment $parentComment,
        ?User $viewer,
        array $followingIds,
        int $postOwnerId,
        string $sort = 'interesting',
        int $limit = 2,
    ): array {
        $query = $this->rankedRepliesQuery(
            (int) $parentComment->id,
            $followingIds,
            $postOwnerId,
            (int) $parentComment->user_id,
            $sort,
        );
        $this->applyAiVisibility($query, $viewer);

        $priorityPreview = $sort === 'interesting'
            ? (clone $query)->havingRaw('smart_rank >= 70')->take($limit)->get()
            : (clone $query)->take($limit)->get();

        if ($priorityPreview->isEmpty()) {
            $priorityPreview = (clone $query)->take($limit)->get();
        }

        $serialized = $priorityPreview
            ->map(fn ($reply) => $this->serializeComment($reply, $viewer))
            ->values()
            ->all();

        return [
            'preview_replies' => $serialized,
            'preview_replies_count' => count($serialized),
            'remaining_replies_count' => max(0, (int) $parentComment->replies_count - count($serialized)),
        ];
    }

public function index(Request $request, $post_id) {
    $user = Auth::guard('user')->user();
    $followingIds = $this->viewerFollowingIds($user);
    $post = BookClub::select('id', 'user_id')->find($post_id);
    $replySort = $this->normalizeReplySort($request->query('reply_sort'));
    $offset = max(0, (int) $request->query('offset', 0));
    $limit = min(30, max(1, (int) $request->query('limit', 24)));

    $query = $this->rankedTopLevelCommentsQuery(
        (int) $post_id,
        $followingIds,
        (int) ($post?->user_id ?? 0),
        $replySort,
    );
    $this->applyAiVisibility($query, $user);

    $total = (clone $query)->count(DB::raw('distinct book_club_comments.id'));

    $comments = (clone $query)
        ->skip($offset)
        ->take($limit)
        ->get()
        ->map(function ($comment) use ($user, $followingIds, $post, $replySort) {
            $preview = ((int) ($comment->replies_count ?? 0) > 0)
                ? $this->replyPreviewBundle($comment, $user, $followingIds, (int) ($post?->user_id ?? 0), $replySort)
                : [
                    'preview_replies' => [],
                    'preview_replies_count' => 0,
                    'remaining_replies_count' => 0,
                ];

            return $this->serializeComment($comment, $user, $preview);
        });

    $shown = $offset + $comments->count();
    $remaining = max(0, $total - $shown);

    return response()->json([
        'status' => 'success',
        'data' => $comments,
        'meta' => [
            'reply_sort' => $replySort,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'remaining_count' => $remaining,
            'next_offset' => $remaining > 0 ? $shown : null,
            'has_more' => $remaining > 0,
        ],
    ], 200);
}

public function replies(Request $request, $comment_id) {
    $user = Auth::guard('user')->user();
    $offset = max(0, (int) $request->query('offset', 0));
    $limit = min(10, max(1, (int) $request->query('limit', 10)));
    $replySort = $this->normalizeReplySort($request->query('sort'));
    $parentComment = BookClubComment::select('id', 'post_id', 'user_id')->findOrFail($comment_id);
    $post = BookClub::select('id', 'user_id')->find($parentComment->post_id);
    $followingIds = $this->viewerFollowingIds($user);

    $query = $this->rankedRepliesQuery(
        (int) $parentComment->id,
        $followingIds,
        (int) ($post?->user_id ?? 0),
        (int) $parentComment->user_id,
        $replySort,
    );
    $this->applyAiVisibility($query, $user);

    $total = (clone $query)->count(DB::raw('distinct book_club_comments.id'));
    $comments = (clone $query)
        ->skip($offset)
        ->take($limit)
        ->get()
        ->map(fn ($comment) => $this->serializeComment($comment, $user))
        ->values();

    $shown = $offset + $comments->count();
    $remaining = max(0, $total - $shown);

    return response()->json([
        'status' => 'success',
        'data' => $comments,
        'meta' => [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'remaining_count' => $remaining,
            'next_offset' => $remaining > 0 ? $shown : null,
            'has_more' => $remaining > 0,
            'sort' => $replySort,
        ],
    ], 200);
}

    public function store(Request $request) {
        $request->validate(['post_id' => 'required', 'content' => 'required']);
        $user = Auth::guard('user')->user();
    
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }
        if ($this->moderationService->isUserBlockedFromWriting((int) $user->id)) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'book_club_user_blocked',
                'message' => "Boshqalarning xavfsizligi uchun siz Book Club va xabar almashish bo'limida vaqtincha bloklangansiz.",
            ], 423);
        }
        $content = (string) $request->content;
        $comment = BookClubComment::create(array_merge([
            'post_id' => $request->post_id,
            'user_id' => $user->id,
            'content' => $content,
            'ai_status' => 'pending',
            'ai_score' => null,
            'ai_checked_at' => null,
            'ai_note' => null,
            'ai_model' => null,
        ], $this->contentPolicy->initialState($content)));

        $hardRisk = (bool) data_get($comment->ai_moderation_meta, 'hard_risk', false);
        $post = BookClub::find($request->post_id);
        if ($post && ! $hardRisk) {
            NotificationHelper::send(
                $post->user_id,
                $user,
                'comment',
                $post->id,
                ['comment_id' => $comment->id]
            );
        }

        if (! $hardRisk) {
            $this->mentionService->notifyMentionedUsers(
                $this->mentionService->extractMentions($comment->content),
                $user,
                'mention',
                (int) $comment->post_id,
                ['comment_id' => $comment->id]
            );
        }

        return response()->json(['status' => 'success', 'data' => $comment], 201);
    }

    public function reply(Request $request, $comment_id) {
    $request->validate([
        'content' => 'required',
        'reply_to_user_id' => 'nullable|integer|exists:users,id',
    ]);

    $user = Auth::guard('user')->user();

    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }
    if ($this->moderationService->isUserBlockedFromWriting((int) $user->id)) {
        return response()->json([
            'status' => 'error',
            'error_code' => 'book_club_user_blocked',
            'message' => "Boshqalarning xavfsizligi uchun siz Book Club va xabar almashish bo'limida vaqtincha bloklangansiz.",
        ], 423);
    }
    $parentComment = BookClubComment::find($comment_id);
    
    if (!$parentComment) {
        return response()->json(['status' => 'error', 'message' => "Javob berilayotgan comment topilmadi!"], 404);
    }
    $replyToUserId = $request->filled('reply_to_user_id') ? (int) $request->reply_to_user_id : null;

    $content = (string) $request->content;
    $reply = BookClubComment::create(array_merge([
        'post_id' => $parentComment->post_id,
        'parent_id' => $comment_id,
        'user_id' => $user->id,
        'content' => $content,
        'reply_to_user_id' => $replyToUserId,
        'ai_status' => 'pending',
        'ai_score' => null,
        'ai_checked_at' => null,
        'ai_note' => null,
        'ai_model' => null,
    ], $this->contentPolicy->initialState($content)));

    $hardRisk = (bool) data_get($reply->ai_moderation_meta, 'hard_risk', false);
    if (! $hardRisk) {
        NotificationHelper::send(
            $parentComment->user_id,
            $user,
            'reply',
            $parentComment->post_id,
            ['comment_id' => $parentComment->id]
        );
    }

    if (! $hardRisk && $replyToUserId && $replyToUserId !== (int) $parentComment->user_id && $replyToUserId !== (int) $user->id) {
        NotificationHelper::send(
            $replyToUserId,
            $user,
            'reply',
            $parentComment->post_id,
            ['comment_id' => $reply->id]
        );
    }

    $post = BookClub::find($parentComment->post_id);
    if (! $hardRisk && $post && $post->user_id !== $parentComment->user_id) {
        NotificationHelper::send(
            $post->user_id,
            $user,
            'comment',
            $post->id,
            ['comment_id' => $reply->id]
        );
    }

    if (! $hardRisk) {
        $this->mentionService->notifyMentionedUsers(
            $this->mentionService->extractMentions($reply->content),
            $user,
            'mention',
            (int) $reply->post_id,
            ['comment_id' => $reply->id]
        );
    }

    return response()->json(['status' => 'success', 'data' => $reply], 201);
}

    public function likeComment(Request $request) {
        $user = Auth::guard('user')->user();
    
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }
        $existingLike = BookClubCommentLike::where('comment_id', $request->comment_id)->where('user_id', $user->id)->first();

        if ($existingLike) {
            $existingLike->delete();

            $comment = BookClubComment::find($request->comment_id);
            if ($comment) {
                $notification = \App\Models\BookClubNotification::where('user_id', $comment->user_id)
                    ->where('group_key', "comment_like_comment_{$comment->id}")
                    ->where('is_read', 0)
                    ->first();

                if ($notification) {
                    NotificationHelper::removeActor($notification, (int) $user->id);
                }
            }
            return response()->json(['status' => 'success'], 201);
        }

        BookClubCommentLike::create(['comment_id' => $request->comment_id, 'user_id' => $user->id]);

        $comment = BookClubComment::find($request->comment_id);
        if ($comment) {
            NotificationHelper::send(
                $comment->user_id,
                $user,
                'comment_like',
                $comment->post_id,
                ['comment_id' => $comment->id]
            );
        }
        return response()->json(['status' => 'success'], 201);
    }

    public function destroy(Request $request, $id) {
        $user = Auth::guard('user')->user();
    
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }
        $comment = BookClubComment::findOrFail($id);
        if ($comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
