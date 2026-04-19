<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\BookClubImages;
use App\Models\BookClubLikes;
use App\Models\User;
use App\Support\BookClubUgcSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookClubController extends Controller
{
    // ── Kangaroo: admin navbati (past ishonch / avtomatik baholanmagan) ──
    public function moderationQueue()
    {
        $pendingComments = BookClubComment::query()
            ->with(['user:id,name,lastname,avatar', 'post:id,text,user_id,product_id,product_type'])
            ->where('kangaroo_ugc_status', 'pending_admin')
            ->whereNull('parent_id')
            ->latest('updated_at')
            ->limit(100)
            ->get();

        $pendingPosts = BookClub::query()
            ->with(['user:id,name,lastname,avatar'])
            ->where('kangaroo_post_ugc_status', 'pending_admin')
            ->where('is_deleted', false)
            ->latest('updated_at')
            ->limit(60)
            ->get();

        return view('panel.book-club.moderation-queue', compact('pendingComments', 'pendingPosts'));
    }

    public function saveCommentUgcScore(Request $request, BookClubComment $comment)
    {
        if ($comment->parent_id !== null) {
            abort(404);
        }
        $data = $request->validate([
            'star' => 'required|numeric|between:1,5',
        ]);
        $comment->update([
            'kangaroo_star_equivalent' => round((float) $data['star'], 2),
            'kangaroo_ugc_status' => 'admin_scored',
            'kangaroo_checked_at' => now(),
        ]);
        BookClubUgcSupport::recalcPostStarFromComments((int) $comment->post_id);
        BookClubUgcSupport::recalcProductUgcFromPosts([(int) $comment->post_id]);

        return back()->with('success', 'Izoh bahosi saqlandi.');
    }

    public function savePostUgcScore(Request $request, BookClub $bookClub)
    {
        $data = $request->validate([
            'star' => 'required|numeric|between:1,5',
        ]);
        $bookClub->update([
            'kangaroo_post_star' => round((float) $data['star'], 2),
            'kangaroo_post_ugc_status' => 'admin_scored',
            'kangaroo_post_checked_at' => now(),
        ]);
        BookClubUgcSupport::recalcProductUgcFromPosts([(int) $bookClub->id]);

        return back()->with('success', 'Post matni bahosi saqlandi.');
    }

    // ── Post ro'yxati (global) ─────────────────────────────────
    public function index(Request $request)
    {
        $q = BookClub::with([
            'user:id,name,lastname,avatar',
            'images',
        ])->where('is_deleted', false);

        $tab = $request->get('tab', 'all');
        match ($tab) {
            'posts' => $q->where('repost', false),
            'reposts' => $q->where('repost', true),
            default => null,
        };

        if ($s = $request->search) {
            $q->where(fn ($x) => $x
                ->where('text', 'like', "%$s%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%")
                )
            );
        }

        if ($request->filled('product_type')) {
            $q->where('product_type', $request->product_type);
        }

        $posts = $q->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => BookClub::where('is_deleted', false)->count(),
            'posts' => BookClub::where('is_deleted', false)->where('repost', false)->count(),
            'reposts' => BookClub::where('is_deleted', false)->where('repost', true)->count(),
        ];

        return view('panel.book-club.index', compact('posts', 'counts', 'tab'));
    }

    // ── Post batafsil ──────────────────────────────────────────
    public function show(BookClub $bookClub)
    {
        $bookClub->load([
            'user:id,name,lastname,avatar,phone_number',
            'originalAuthor:id,name,lastname,avatar',
            'images',
            'votes',
        ]);

        // Izohlar (faqat top-level)
        $comments = BookClubComment::where('post_id', $bookClub->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,lastname,avatar',
                'likes',
                'replies.user:id,name,lastname,avatar',
                'replies.likes',
            ])
            ->withCount(['likes', 'replies'])
            ->latest()
            ->paginate(20);

        // Like bosganlar
        $likers = BookClubLikes::where('post_id', $bookClub->id)
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest()
            ->take(20)
            ->get();

        $likesCount = BookClubLikes::where('post_id', $bookClub->id)->count();

        // Repost qilganlar
        $reposters = BookClub::where('repost', true)
            ->where('reposted_user_id', $bookClub->user_id)
            ->where('product_id', $bookClub->product_id)
            ->where('text', $bookClub->text)
            ->with('user:id,name,lastname,avatar')
            ->latest()
            ->take(20)
            ->get();

        $repostsCount = $reposters->count();

        // Votes
        $totalVotes = $bookClub->votes->sum(fn ($v) => \DB::table('book_club_voted_users')->where('option_id', $v->id)->count()
        );

        return view('panel.book-club.show', compact(
            'bookClub', 'comments', 'likers', 'likesCount',
            'reposters', 'repostsCount', 'totalVotes'
        ));
    }

    // ── Post tahrirlash ────────────────────────────────────────
    public function edit(BookClub $bookClub)
    {
        $bookClub->load(['user:id,name,lastname,avatar', 'images', 'votes']);

        return view('panel.book-club.edit', compact('bookClub'));
    }

    public function update(Request $request, BookClub $bookClub)
    {
        $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $bookClub->update(['text' => $request->text]);

        return redirect()->route('panel.book-club.show', $bookClub)
            ->with('success', 'Post yangilandi.');
    }

    // ── Post o'chirish (soft) ──────────────────────────────────
    public function destroy(BookClub $bookClub)
    {
        $bookClub->update(['is_deleted' => true]);

        return redirect()->route('panel.book-club.index')
            ->with('success', "Post o'chirildi.");
    }

    // ── Rasm o'chirish ─────────────────────────────────────────
    public function deleteImage(BookClubImages $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();

        return back()->with('success', 'Rasm o\'chirildi.');
    }

    // ── Izoh o'chirish ─────────────────────────────────────────
    public function deleteComment(BookClubComment $comment)
    {
        // Javoblarni ham o'chirish
        BookClubComment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        return back()->with('success', "Izoh o'chirildi.");
    }

    // ── Izoh tahrirlash ────────────────────────────────────────
    public function updateComment(Request $request, BookClubComment $comment)
    {
        $request->validate(['content' => 'required|string|max:1000']);
        $comment->update(['content' => $request->content]);

        return back()->with('success', 'Izoh yangilandi.');
    }

    // ── User profili postlari ──────────────────────────────────
    public function userPosts(User $user, Request $request)
    {
        $tab = $request->get('tab', 'posts');

        $q = BookClub::with(['user:id,name,lastname,avatar', 'images'])
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->withCount(['likes', 'comments']);

        match ($tab) {
            'reposts' => $q->where('repost', true),
            default => $q->where('repost', false),
        };

        $posts = $q->latest()->paginate(10, ['*'], 'page')->withQueryString();

        $postsCount = BookClub::where('user_id', $user->id)->where('is_deleted', false)->where('repost', false)->count();
        $repostsCount = BookClub::where('user_id', $user->id)->where('is_deleted', false)->where('repost', true)->count();

        return compact('posts', 'postsCount', 'repostsCount', 'tab');
    }
}
