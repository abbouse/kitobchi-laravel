<?php

namespace App\Http\Controllers\A122;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\StationeryVariant;
use App\Services\ProductModerationStateService;
use App\Support\ProductImageUrls;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StationeryController extends Controller
{
    public function __construct(
        private readonly ProductModerationStateService $productModerationState,
    ) {}

    private function parseImagesText(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $query = Stationery::with('category');
        $tab = match ((string) $request->input('tab', 'pending')) {
            'active' => 'active',
            'rejected', 'reject' => 'rejected',
            'all' => 'all',
            default => 'pending',
        };

        match ($tab) {
            'active' => $query->where('is_approved', 1),
            'rejected' => $query->where('is_approved', 2),
            'all' => null,
            default => $query->where('is_approved', 0),
        };

        if ($search = $request->input('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('artikul', 'like', "%{$search}%")
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
        $sellers = Seller::query()
            ->select('id', 'shop_name')
            ->whereNotNull('shop_name')
            ->orderBy('shop_name')
            ->get();

        return view('a122.stationery.create', compact('categories', 'sellers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'seller_id' => 'nullable|exists:sellers,id',
            'barcode' => 'nullable|string|max:32',
            'material' => 'nullable|string|max:255',
            'category_id' => 'required|exists:stationery_categories,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'discountExpiresAt' => 'nullable|date',
            'stock' => 'required|integer|min:0',
            'is_approved' => 'nullable|in:0,1,2',
            'status' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            'recommendedExpiresAt' => 'nullable|date',
            'is_hidden' => 'nullable|boolean',
            'images_text' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'variant_id.*' => 'nullable|integer|exists:stationery_variants,id',
            'variant_color_name.*' => 'nullable|string|max:100',
            'variant_stock.*' => 'nullable|integer|min:0',
            'variant_image_existing.*' => 'nullable|string|max:1000',
            'variant_image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $images = $this->parseImagesText($request->input('images_text'));
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if (! $image->isValid()) {
                    continue;
                }
                $filename = time()."_admin_stationery_{$index}.".$image->getClientOriginalExtension();
                $path = $image->storeAs('stationery', $filename, 'public');
                $images[] = $path;
                ProductImageVariantGenerator::generateForPath($path);
            }
        }

        $data['images'] = array_values(array_unique($images));
        $data['status'] = $request->boolean('status', true);
        $data['recommended'] = $request->boolean('recommended', false);
        $data['is_hidden'] = $request->boolean('is_hidden', false);
        $data['is_approved'] = 0;

        $item = Stationery::create($data);
        $this->syncVariants($request, $item);
        $this->productModerationState->markPending($item, 'admin_created');

        return redirect()->route('admin.stationery.show', $item->id)->with('success', 'Kanstovar yaratildi.');
    }

    public function show(int $id)
    {
        $item = Stationery::with(['category', 'seller', 'variants'])->findOrFail($id);
        $images = collect($item->images ?? [])
            ->filter(fn ($image) => is_string($image) && trim($image) !== '')
            ->map(fn (string $image) => ProductImageUrls::originalUrl($image))
            ->filter()
            ->values();
        $item->variants->transform(function (StationeryVariant $variant) {
            $variant->image_url = ProductImageUrls::originalUrl($variant->image_path);

            return $variant;
        });
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
        $item = Stationery::with('variants')->findOrFail($id);
        $categories = StationeryCategory::orderBy('name_uz')->get();
        $sellers = Seller::query()
            ->select('id', 'shop_name')
            ->whereNotNull('shop_name')
            ->orderBy('shop_name')
            ->get();

        return view('a122.stationery.edit', compact('item', 'categories', 'sellers'));
    }

    public function update(Request $request, int $id)
    {
        $item = Stationery::with('variants')->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'seller_id' => 'nullable|exists:sellers,id',
            'barcode' => 'nullable|string|max:32',
            'material' => 'nullable|string|max:255',
            'category_id' => 'required|exists:stationery_categories,id',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'discountExpiresAt' => 'nullable|date',
            'stock' => 'required|integer|min:0',
            'is_approved' => 'nullable|in:0,1,2',
            'status' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            'recommendedExpiresAt' => 'nullable|date',
            'is_hidden' => 'nullable|boolean',
            'images_text' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'variant_id.*' => 'nullable|integer|exists:stationery_variants,id',
            'variant_color_name.*' => 'nullable|string|max:100',
            'variant_stock.*' => 'nullable|integer|min:0',
            'variant_image_existing.*' => 'nullable|string|max:1000',
            'variant_image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $existingImages = $this->parseImagesText($request->input('images_text'));
        $currentImages = is_array($item->images) ? $item->images : [];
        $deletedImages = array_diff($currentImages, $existingImages);
        foreach ($deletedImages as $image) {
            if (is_string($image) && ! str_starts_with($image, 'http')) {
                Storage::disk('public')->delete($image);
                ProductImageVariantGenerator::deleteForPath($image);
            }
        }

        $images = $existingImages;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if (! $image->isValid()) {
                    continue;
                }
                $filename = time()."_admin_stationery_{$index}.".$image->getClientOriginalExtension();
                $path = $image->storeAs('stationery', $filename, 'public');
                $images[] = $path;
                ProductImageVariantGenerator::generateForPath($path);
            }
        }

        $data['images'] = array_values(array_unique($images));
        $data['status'] = $request->boolean('status', true);
        $data['recommended'] = $request->boolean('recommended', false);
        $data['is_hidden'] = $request->boolean('is_hidden', false);

        $item->update($data);
        $this->syncVariants($request, $item);
        $this->productModerationState->markPending($item, 'admin_edited');

        return redirect()->route('admin.stationery.show', $item->id)->with('success', 'Kanstovar yangilandi.');
    }

    private function syncVariants(Request $request, Stationery $item): void
    {
        $variantIds = (array) $request->input('variant_id', []);
        $variantNames = (array) $request->input('variant_color_name', []);
        $variantStocks = (array) $request->input('variant_stock', []);
        $variantExistingImages = (array) $request->input('variant_image_existing', []);
        $variantUploadedImages = $request->file('variant_image', []);

        $incomingIds = [];
        foreach ($variantNames as $index => $name) {
            $name = trim((string) $name);
            $stock = $variantStocks[$index] ?? null;
            if ($name === '' && ($stock === null || $stock === '')) {
                continue;
            }

            $variantId = $variantIds[$index] ?? null;
            $imagePath = trim((string) ($variantExistingImages[$index] ?? ''));
            $uploaded = $variantUploadedImages[$index] ?? null;
            if ($uploaded && $uploaded->isValid()) {
                if ($imagePath !== '' && ! str_starts_with($imagePath, 'http')) {
                    Storage::disk('public')->delete($imagePath);
                    ProductImageVariantGenerator::deleteForPath($imagePath);
                }
                $filename = time()."_admin_variant_{$index}.".$uploaded->getClientOriginalExtension();
                $imagePath = $uploaded->storeAs('stationery/variants', $filename, 'public');
                ProductImageVariantGenerator::generateForPath($imagePath);
            }

            $payload = [
                'color_name' => $name,
                'stock' => (int) $stock,
                'image_path' => $imagePath !== '' ? $imagePath : null,
            ];

            if ($variantId) {
                $variant = $item->variants->firstWhere('id', (int) $variantId);
                if ($variant) {
                    $variant->update($payload);
                    $incomingIds[] = (int) $variant->id;
                }
            } else {
                $created = $item->variants()->create($payload);
                $incomingIds[] = (int) $created->id;
            }
        }

        $toDelete = $item->variants()->whereNotIn('id', $incomingIds ?: [0])->get();
        foreach ($toDelete as $variant) {
            if ($variant->image_path && ! str_starts_with($variant->image_path, 'http')) {
                Storage::disk('public')->delete($variant->image_path);
                ProductImageVariantGenerator::deleteForPath($variant->image_path);
            }
            $variant->delete();
        }
    }

    public function moderate(Request $request, int $id)
    {
        $request->validate([
            'is_approved' => 'required|in:0,1,2',
            'note' => 'nullable|string|max:500',
        ]);

        $item = Stationery::findOrFail($id);
        $approval = (int) $request->input('is_approved');
        $note = trim((string) $request->input('note'));
        $item->updateQuietly([
            'is_approved' => $approval,
            'ai_moderation_status' => match ($approval) {
                1 => 'manual_approved',
                2 => 'manual_rejected',
                default => 'pending',
            },
            'ai_moderation_checked_at' => now(),
            'ai_moderation_note' => $note !== ''
                ? $note
                : ($approval === 2 ? 'Admin tomonidan rad etildi.' : 'Admin tomonidan qo‘lda moderatsiya qilindi.'),
            'ai_moderation_next_retry_at' => null,
            'ai_moderation_meta' => ['source' => 'admin_manual_override', 'manual_note' => $note ?: null],
        ]);

        return back()->with('success', $approval === 2 ? 'Kanstovar rad etildi.' : 'Kanstovar moderatsiyasi yangilandi.');
    }
}
