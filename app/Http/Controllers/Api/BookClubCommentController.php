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
use Illuminate\Support\Facades\Auth;

class BookClubCommentController extends Controller {
    public function __construct(
        private readonly BookClubModerationService $moderationService,
    ) {}
    
    public function index(Request $request, $post_id) {
    // 1. Userni olish (Guest bo'lsa null qaytadi)
    $user = Auth::guard('user')->user();

    // 2. Kommentlarni olish (withCount orqali replies sonini bazaning o'zida hisoblaymiz)
    $comments = BookClubComment::where('post_id', $post_id)
        ->whereNull('parent_id') // parent_id == null degani
        ->with(['user:id,name,lastname,sex,avatar,position,isVerified,isSupport,role_emoji,role_title,role_place', 'likes'])
        ->withCount('replies') // Modelda 'replies' relation bo'lishi kerak
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($comment) use ($user) {
            return [
                'id'         => $comment->id,
                'post_id'    => $comment->post_id,
                'content'    => $comment->content,
                'created_at' => $comment->created_at,
                'user'       => $comment->user,
                'likes'      => $comment->likes->count(),
                // Xavfsiz tekshiruv:
                'liked'      => $user ? $comment->likes->contains('user_id', $user->id) : false,
                'replies_count' => $comment->replies_count, // withCount dan keladi
            ];
        });

    return response()->json(['status' => 'success', 'data' => $comments], 200);
}

public function replies(Request $request, $comment_id) {
    $user = Auth::guard('user')->user();
    $comments = BookClubComment::where('parent_id', $comment_id)
        ->with(['user:id,name,lastname,sex,avatar,position,isVerified,isSupport,role_emoji,role_title,role_place', 'likes.user:id,name,avatar'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($comment) use ($user) {
            return [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'parent_id' => $comment->post_id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'user' => $comment->user,
                'likes' => $comment->likes->count(),
                'liked' => $user ? $comment->likes->contains('user_id', $user->id) : false,
                'replies_count' => BookClubComment::where('parent_id', $comment->id)->count(),
            ];
        });

    return response()->json(['status' => 'success', 'data' => $comments], 201);
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
        $comment = BookClubComment::create([
            'post_id' => $request->post_id,
            'user_id' => $user->id,
            'content' => $request->content
        ]);

        $post = BookClub::find($request->post_id);
        if ($post) {
            NotificationHelper::send(
                $post->user_id,
                $user,
                'comment',
                $post->id,
                ['comment_id' => $comment->id]
            );
        }

        return response()->json(['status' => 'success', 'data' => $comment], 201);
    }

    public function reply(Request $request, $comment_id) {
    $request->validate([
        'content' => 'required',
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
    $reply = BookClubComment::create([
        'post_id' => $parentComment->post_id,
        'parent_id' => $comment_id,
        'user_id' => $user->id,
        'content' => $request->content
    ]);

    NotificationHelper::send(
        $parentComment->user_id,
        $user,
        'reply',
        $parentComment->post_id,
        ['comment_id' => $parentComment->id]
    );

    $post = BookClub::find($parentComment->post_id);
    if ($post && $post->user_id !== $parentComment->user_id) {
        NotificationHelper::send(
            $post->user_id,
            $user,
            'comment',
            $post->id,
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
