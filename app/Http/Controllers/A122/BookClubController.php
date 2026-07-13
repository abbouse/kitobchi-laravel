<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BookClub;
use App\Models\BookClubComment;
use App\Models\BookClubImages;
use App\Models\BookClubLikes;
use App\Models\BookClubWarning;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookClubController extends Controller
{
    public function index(Request $request)
    {
        $q = BookClub::with(['user:id,name,lastname,avatar', 'activeWarning'])->where('is_deleted', false);

        $tab = $request->get('tab', 'all');
        match ($tab) {
            'posts'   => $q->where('repost', false),
            'reposts' => $q->where('repost', true),
            default   => null,
        };

        if ($s = $request->search) {
            $q->where(fn($x) => $x
                ->where('text', 'like', "%$s%")
                ->orWhereHas('user', fn($u) => $u
                    ->where('name', 'like', "%$s%")
                    ->orWhere('phone_number', 'like', "%$s%")
                )
            );
        }

        if ($request->filled('product_type')) {
            $q->where('product_type', $request->product_type);
        }

        $posts = $q->withCount(['likes', 'comments'])->latest()->paginate(20)->withQueryString();

        $counts = [
            'all'     => BookClub::where('is_deleted', false)->count(),
            'posts'   => BookClub::where('is_deleted', false)->where('repost', false)->count(),
            'reposts' => BookClub::where('is_deleted', false)->where('repost', true)->count(),
        ];

        return view('a122.book-club.index', compact('posts', 'counts', 'tab'));
    }

    public function show(BookClub $bookClub)
    {
        $bookClub->load([
            'user:id,name,lastname,avatar,phone_number',
            'originalAuthor:id,name,lastname,avatar',
            'images',
            'votes',
            'activeWarning',
            'warnings.admin:id,name,email',
        ]);
        $activeWarningCount = BookClubWarning::active()->where('user_id', $bookClub->user_id)->count();
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

        $likers = BookClubLikes::where('post_id', $bookClub->id)
            ->with('user:id,name,lastname,avatar,phone_number')
            ->latest()
            ->take(20)
            ->get();

        $likesCount = BookClubLikes::where('post_id', $bookClub->id)->count();

        $reposters = BookClub::where('repost', true)
            ->where('reposted_user_id', $bookClub->user_id)
            ->where('product_id', $bookClub->product_id)
            ->where('text', $bookClub->text)
            ->with('user:id,name,lastname,avatar')
            ->latest()
            ->take(20)
            ->get();

        $repostsCount = $reposters->count();

        $totalVotes = $bookClub->votes->sum(fn ($vote) => \DB::table('book_club_voted_users')->where('option_id', $vote->id)->count());

        $relatedProduct = null;
        if ($bookClub->product_id && $bookClub->product_type === 'book') {
            $relatedProduct = Books::with('seller')
                ->select('id', 'name', 'author', 'seller_id')
                ->find($bookClub->product_id);
        } elseif ($bookClub->product_id && $bookClub->product_type === 'stationery') {
            $relatedProduct = Stationery::with('seller')
                ->select('id', 'name', 'seller_id')
                ->find($bookClub->product_id);
        }

        return view('a122.book-club.show', compact(
            'bookClub',
            'comments',
            'likers',
            'likesCount',
            'reposters',
            'repostsCount',
            'totalVotes',
            'activeWarningCount',
            'relatedProduct'
        ));
    }

    public function edit(BookClub $bookClub)
    {
        $bookClub->load(['user:id,name,lastname,avatar', 'images', 'votes']);

        return view('a122.book-club.edit', compact('bookClub'));
    }

    public function update(Request $request, BookClub $bookClub)
    {
        $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $aiPayload = $bookClub->repost
            ? [
                'ai_post_status' => 'skipped_repost',
                'ai_post_note' => 'Repost: original post AI bahosi ishlatiladi',
                'ai_post_checked_at' => now(),
            ]
            : [
                'ai_post_status' => 'pending',
                'ai_post_score' => null,
                'ai_post_checked_at' => null,
                'ai_post_note' => null,
                'ai_post_model' => null,
            ];

        $bookClub->update(array_merge([
            'text' => $request->text,
        ], $aiPayload));

        return redirect()->route('admin.book-club.show', $bookClub)->with('success', 'Post yangilandi.');
    }

    public function destroy(BookClub $bookClub)
    {
        $bookClub->update(['is_deleted' => true]);
        return redirect()->route('admin.book-club.index')->with('success', "Post o'chirildi.");
    }

    public function warn(Request $request, BookClub $bookClub)
    {
        $data = $request->validate([
            'note' => 'required|string|min:5|max:2000',
        ]);

        $warning = BookClubWarning::active()->where('post_id', $bookClub->id)->first();

        if ($warning) {
            $warning->update([
                'note' => $data['note'],
                'admin_id' => optional(auth('panel')->user())->id,
            ]);

            return back()->with('success', 'Post ogohlantirish izohi yangilandi.');
        }

        BookClubWarning::create([
            'user_id' => $bookClub->user_id,
            'post_id' => $bookClub->id,
            'admin_id' => optional(auth('panel')->user())->id,
            'note' => $data['note'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Post ogohlantirildi.');
    }

    public function deleteComment(BookClubComment $comment)
    {
        BookClubComment::where('parent_id', $comment->id)->delete();
        $comment->delete();
        return back()->with('success', "Izoh o'chirildi.");
    }

    public function updateComment(Request $request, BookClubComment $comment)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $request->content,
            'ai_status' => 'pending',
            'ai_score' => null,
            'ai_checked_at' => null,
            'ai_note' => null,
            'ai_model' => null,
        ] + app(\App\Services\BookClubContentPolicy::class)->initialState((string) $request->content));

        return back()->with('success', 'Izoh yangilandi.');
    }

    public function deleteImage(BookClubImages $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();

        return back()->with('success', "Rasm o'chirildi.");
    }
}
