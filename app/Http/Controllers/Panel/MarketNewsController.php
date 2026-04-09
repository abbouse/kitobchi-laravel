<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MarketNews;
use App\Models\Seller;
use App\Models\Books;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketNewsController extends Controller
{
    // ── Index ──────────────────────────────────────────────
    public function index()
    {
        $news = MarketNews::with(['seller:id,shop_name,photo', 'book:id,name,images'])
            ->latest()
            ->paginate(20);

        $counts = [
            'all'    => MarketNews::count(),
            'active' => MarketNews::where('status', true)->count(),
            'news'   => MarketNews::where('action', 'news')->count(),
        ];

        return view('panel.market-news.index', compact('news', 'counts'));
    }

    // ── Create ─────────────────────────────────────────────
    public function create()
    {
        return view('panel.market-news.create');
    }

    // ── Store ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $this->validateNews($request);

        if ($request->hasFile('imgUrl')) {
            $data['imgUrl'] = $request->file('imgUrl')
                ->store('blog', 'public');
        }

        $news = MarketNews::create($data);

        return redirect()->route('panel.market-news.show', $news)
            ->with('success', 'Yangilik yaratildi.');
    }

    // ── Show ───────────────────────────────────────────────
    public function show(MarketNews $marketNews)
    {
        // Action preview: seller yoki book
        $preview = null;

        if ($marketNews->action === 'to_shop' && $marketNews->action_id) {
            $preview = Seller::select('id', 'shop_name', 'photo', 'description',
                                      'rating', 'isVerified', 'phone_number')
                ->find($marketNews->action_id);
        } elseif ($marketNews->action === 'to_product' && $marketNews->action_id) {
            $preview = Books::select('id', 'name', 'images', 'price',
                                     'discountPrice', 'author', 'description')
                ->find($marketNews->action_id);
        }

        return view('panel.market-news.show',
            compact('marketNews', 'preview'));
    }

    // ── Edit ───────────────────────────────────────────────
    public function edit(MarketNews $marketNews)
    {
        return view('panel.market-news.edit', compact('marketNews'));
    }

    // ── Update ─────────────────────────────────────────────
    public function update(Request $request, MarketNews $marketNews)
    {
        $data = $this->validateNews($request, $marketNews);

        if ($request->hasFile('imgUrl')) {
            if ($marketNews->imgUrl) {
                Storage::disk('public')->delete($marketNews->imgUrl);
            }
            $data['imgUrl'] = $request->file('imgUrl')
                ->store('blog', 'public');
        }

        $marketNews->update($data);

        return redirect()->route('panel.market-news.show', $marketNews)
            ->with('success', 'Yangilik yangilandi.');
    }

    // ── Toggle status ──────────────────────────────────────
    public function toggle(MarketNews $marketNews)
    {
        $marketNews->update(['status' => !$marketNews->status]);
        $msg = $marketNews->status ? 'Faollashtirildi.' : 'O\'chirildi.';
        return back()->with('success', $msg);
    }

    // ── Destroy ────────────────────────────────────────────
    public function destroy(MarketNews $marketNews)
    {
        if ($marketNews->imgUrl) {
            Storage::disk('public')->delete($marketNews->imgUrl);
        }
        $marketNews->delete();

        return redirect()->route('panel.market-news.index')
            ->with('success', 'Yangilik o\'chirildi.');
    }

    // ── AJAX: action_id preview ────────────────────────────
    // Flutter preview uchun: ?action=to_shop&id=5
    public function previewAction(Request $request)
    {
        $action = $request->action;
        $id     = (int) $request->id;

        if (!$id) {
            return response()->json(['found' => false]);
        }

        if ($action === 'to_shop') {
            $seller = Seller::select('id', 'shop_name', 'photo',
                                     'rating', 'isVerified', 'phone_number')
                ->find($id);

            if (!$seller) return response()->json(['found' => false]);

            return response()->json([
                'found' => true,
                'type'  => 'shop',
                'id'    => $seller->id,
                'name'  => $seller->shop_name,
                'image' => $seller->photo
                    ? asset('storage/'.$seller->photo) : null,
                'rating'     => $seller->rating,
                'isVerified' => (bool) $seller->isVerified,
                'phone'      => $seller->phone_number,
                'url'        => route('panel.sellers.show', $seller->id),
            ]);
        }

        if ($action === 'to_product') {
            $book = Books::select('id', 'name', 'images', 'price',
                                  'discountPrice', 'author')
                ->find($id);

            if (!$book) return response()->json(['found' => false]);

            $images = is_array($book->images) ? $book->images : json_decode($book->images ?? '[]', true);
            $image  = $images[0] ?? null;

            return response()->json([
                'found'  => true,
                'type'   => 'product',
                'id'     => $book->id,
                'name'   => $book->name,
                'author' => $book->author,
                'image'  => $image ? asset('storage/'.$image) : null,
                'price'  => $book->discountPrice > 0 ? $book->discountPrice : $book->price,
                'url'    => route('panel.books.show', $book->id),
            ]);
        }

        return response()->json(['found' => false]);
    }

    // ── Validation helper ──────────────────────────────────
    private function validateNews(Request $request, ?MarketNews $news = null): array
    {
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'align'       => 'required|in:top,center',
            'status'      => 'boolean',
            'action'      => 'required|in:news,to_shop,to_product',
            'action_id'   => 'nullable|integer|min:1',
            'imgUrl'      => $news ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096'
                                   : 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];

        $data = $request->validate($rules);

        // action_id faqat to_shop/to_product uchun saqlanadi
        if ($data['action'] === 'news') {
            $data['action_id'] = null;
        }

        $data['status'] = $request->boolean('status');

        return $data;
    }
}