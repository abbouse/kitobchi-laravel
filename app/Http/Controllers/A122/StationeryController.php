<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use Illuminate\Http\Request;

class StationeryController extends Controller
{
    public function index(Request $request)
    {
        $query = Stationery::with('category');
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
                ->orWhere('material', 'like', "%{$search}%")
                ->orWhere('id', $search));
        }

        $items = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Stationery::count(),
            'pending' => Stationery::where('is_approved', 0)->count(),
            'active' => Stationery::where('is_approved', 1)->count(),
            'rejected' => Stationery::where('is_approved', 2)->count(),
        ];
        $rows = $items->map(function (Stationery $s) {
            return [
                'id' => $s->id,
                'title' => $s->name,
                'category' => $s->category?->name_uz ?: '—',
                'price' => number_format((float) $s->price, 0).' UZS',
                'stock' => (int) ($s->stock ?? 0),
                'status' => (int) ($s->is_approved ?? 0) === 1 ? 'active' : ((int) ($s->is_approved ?? 0) === 2 ? 'banned' : 'pending'),
            ];
        })->values();

        return view('a122.stationery.index', compact('items', 'rows', 'counts', 'tab'));
    }

    public function create()
    {
        $categories = StationeryCategory::orderBy('name_uz')->get();

        return view('a122.stationery.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'material' => 'nullable|string|max:255',
            'category_id' => 'required|exists:stationery_categories,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_approved' => 'nullable|in:0,1,2',
            'status' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
        ]);

        $data['status'] = $request->boolean('status', true);
        $data['recommended'] = $request->boolean('recommended', false);
        $data['is_approved'] = $data['is_approved'] ?? 1;

        $item = Stationery::create($data);

        return redirect()->route('admin.stationery.show', $item->id)->with('success', 'Kanstovar yaratildi.');
    }

    public function show(int $id)
    {
        $item = Stationery::with(['category', 'seller', 'variants'])->findOrFail($id);
        $images = collect($item->images ?? [])->filter()->values();
        $sellerOrders = $item->seller_id
            ? SellerOrder::query()
                ->with(['client:id,name,lastname,phone_number'])
                ->where('seller_id', $item->seller_id)
                ->latest()
                ->take(6)
                ->get()
            : collect();
        $recentOrders = Sold::query()
            ->with('user:id,name,lastname,phone_number')
            ->latest()
            ->take(60)
            ->get()
            ->filter(function (Sold $order) use ($item) {
                return collect($order->items ?? [])->contains(fn ($row) => (int) ($row['item_id'] ?? 0) === (int) $item->id && ($row['type'] ?? 'book') === 'stationery');
            })
            ->take(6)
            ->values();

        return view('a122.stationery.show', compact('item', 'images', 'sellerOrders', 'recentOrders'));
    }

    public function edit(int $id)
    {
        $item = Stationery::findOrFail($id);
        $categories = StationeryCategory::orderBy('name_uz')->get();

        return view('a122.stationery.edit', compact('item', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $item = Stationery::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'material' => 'nullable|string|max:255',
            'category_id' => 'required|exists:stationery_categories,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_approved' => 'nullable|in:0,1,2',
            'status' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
        ]);

        $data['status'] = $request->boolean('status', false);
        $data['recommended'] = $request->boolean('recommended', false);

        $item->update($data);

        return redirect()->route('admin.stationery.show', $item->id)->with('success', 'Kanstovar yangilandi.');
    }

    public function moderate(Request $request, int $id)
    {
        $request->validate(['is_approved' => 'required|in:0,1,2']);

        $item = Stationery::findOrFail($id);
        $item->update(['is_approved' => (int) $request->input('is_approved')]);

        return back()->with('success', 'Kanstovar moderatsiyasi yangilandi.');
    }
}
