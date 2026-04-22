<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\BookCategories;
use App\Models\Books;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Books::with(['category']);
        $tab = $request->input('tab', 'pending');

        match ($tab) {
            'active' => $query->where('is_approved', 1),
            'rejected' => $query->where('is_approved', 2),
            'all' => null,
            default => $query->where('is_approved', 0),
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('id', $search));
        }

        $books = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Books::count(),
            'pending' => Books::where('is_approved', 0)->count(),
            'active' => Books::where('is_approved', 1)->count(),
            'rejected' => Books::where('is_approved', 2)->count(),
        ];

        $rows = $books->map(function (Books $b) {
            return [
                'id' => $b->id,
                'title' => $b->name,
                'author' => $b->author ?: '—',
                'category' => $b->category?->name_uz ?: '—',
                'price' => number_format((float) $b->price, 0).' UZS',
                'stock' => (int) ($b->count ?? 0),
                'sold' => (int) ($b->totalSales ?? 0),
                'status' => (int) ($b->is_approved ?? 0) === 1 ? 'active' : ((int) ($b->is_approved ?? 0) === 2 ? 'banned' : 'pending'),
            ];
        })->values();

        return view('a122.books.index', compact('books', 'rows', 'counts', 'tab'));
    }

    public function create()
    {
        $categories = BookCategories::orderBy('name_uz')->get();
        return view('a122.books.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'category_id' => 'required|exists:book_categories,id',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'count' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
        ]);
        $data['is_approved'] = 1;
        $data['status'] = (bool)($data['status'] ?? true);
        $book = Books::create($data);
        return redirect()->route('admin.books.show', $book)->with('success', 'Yangi kitob yaratildi.');
    }

    public function show(Books $book)
    {
        $book->load(['category', 'seller']);
        $images = collect($book->images ?? [])->filter()->values();
        $sellerOrders = $book->seller_id
            ? SellerOrder::query()
                ->with(['client:id,name,lastname,phone_number'])
                ->where('seller_id', $book->seller_id)
                ->latest()
                ->take(6)
                ->get()
            : collect();
        $recentOrders = Sold::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(60)
            ->get()
            ->filter(function (Sold $order) use ($book) {
                return collect($order->items ?? [])->contains(fn ($item) => (int) ($item['item_id'] ?? 0) === (int) $book->id && ($item['type'] ?? 'book') === 'book');
            })
            ->take(6)
            ->values();

        return view('a122.books.show', compact('book', 'images', 'sellerOrders', 'recentOrders'));
    }

    public function edit(Books $book)
    {
        $categories = BookCategories::orderBy('name_uz')->get();
        return view('a122.books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Books $book)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'category_id' => 'required|exists:book_categories,id',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'count' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
            'is_approved' => 'nullable|in:0,1,2',
        ]);
        $data['status'] = (bool)($data['status'] ?? true);
        $book->update($data);
        return redirect()->route('admin.books.show', $book)->with('success', "Kitob yangilandi.");
    }

    public function moderate(Request $request, Books $book)
    {
        $request->validate(['is_approved' => 'required|in:0,1,2']);
        $book->update(['is_approved' => $request->input('is_approved')]);
        return back()->with('success', 'Moderatsiya yangilandi.');
    }
}
