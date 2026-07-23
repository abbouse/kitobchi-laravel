<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Gifts;
use App\Models\SellerStaffLog;
use App\Support\ProductArtikul;
use App\Support\ProductImageVariantGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    private function hasGiftAccess($seller)
    {
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    private function assignGeneratedArtikul(Gifts $gift): void
    {
        if ($gift->artikul) {
            return;
        }

        $gift->forceFill([
            'artikul' => ProductArtikul::generate('gift', (int) $gift->id),
        ])->save();
    }

    public function list(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
        $search = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'all');

        $baseQuery = Gifts::query()
            ->where('seller_id', $storeSellerId)
            ->whereNull('archived_at');
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)
                ->where('status', true)
                ->where('is_approved', 1)
                ->inStock()
                ->count(),
            'out_stock' => (clone $baseQuery)->whereStockAvailable('<=', 0)->count(),
            'pending' => (clone $baseQuery)
                ->where(fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0))
                ->count(),
            'rejected' => (clone $baseQuery)->where('is_approved', 2)->count(),
        ];

        $query = Gifts::query()
            ->where('seller_id', $storeSellerId)
            ->whereNull('archived_at');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('artikul', 'like', "%{$search}%");
            });
        }

        match ($filter) {
            'active' => $query
                ->where('status', true)
                ->where('is_approved', 1)
                ->inStock(),
            'out_stock' => $query->whereStockAvailable('<=', 0),
            'pending' => $query->where(
                fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0)
            ),
            'rejected' => $query->where('is_approved', 2),
            default => null,
        };

        $gifts = $query->latest('updated_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $gifts->items(),
            'meta' => [
                'current_page' => $gifts->currentPage(),
                'last_page' => $gifts->lastPage(),
                'per_page' => $gifts->perPage(),
                'total' => $gifts->total(),
                'has_more' => $gifts->hasMorePages(),
            ],
            'counts' => $counts,
        ], 200);
    }

    public function create(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'stock'     => 'required|integer|min:0',
            'min_price' => 'required|integer|min:0',
            'max_price' => 'required|integer|gte:min_price',
            'images'    => 'required|array|min:1|max:8',
            'images.*'  => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $paths = $this->uploadImages($request->file('images', []));

        $gift = Gifts::create([
            'seller_id' => $this->getStoreSellerId($seller),
            'name'      => $request->name,
            'priceFrom' => (int) $request->min_price,
            'priceTo'   => (int) $request->max_price,
            'images'     => $paths, // to'g'ridan-to'g'ri array beramiz, Laravel json_encode qiladi
        ]);
        $this->assignGeneratedArtikul($gift);

        // FILIAL STOCK: kirim hodim filialiga (bo'lmasa asosiy filialga)
        app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
            'gift', (int) $gift->id, 0, (int) $this->getStoreSellerId($seller),
            (int) $request->stock,
            $seller->seller_location_id ? (int) $seller->seller_location_id : null,
            ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Sovg\'a yaratildi']
        );

        $this->writeLog($seller, 'Created gift', "Gift ID: {$gift->id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift yaratildi',
            'gift'    => $gift // images accessor ishlaydi
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $gift = Gifts::where('id', $id)
            ->where('seller_id', $this->getStoreSellerId($seller))
            ->whereNull('archived_at')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'stock'            => 'required|integer|min:0',
            'min_price'        => 'required|integer|min:0',
            'max_price'        => 'required|integer|gte:min_price',
            'images.*'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'existing_images'  => 'nullable|array',
            'existing_images.*'=> 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $currentImages = array_values($gift->images ?? []);
        $requestedExistingImages = $request->input('existing_images');
        if (! is_array($requestedExistingImages)) {
            $legacyImages = json_decode((string) $request->input('old_images', '[]'), true);
            $requestedExistingImages = is_array($legacyImages) ? $legacyImages : [];
        }
        $existingImages = array_values(array_intersect(
            $currentImages,
            is_array($requestedExistingImages) ? $requestedExistingImages : []
        ));
        $newImageCount = count($request->file('images', []));
        $totalImageCount = count($existingImages) + $newImageCount;

        if ($totalImageCount < 1 || $totalImageCount > 8) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'images' => [
                        $totalImageCount < 1
                            ? 'Sovg‘a uchun kamida bitta rasm kerak.'
                            : 'Ko‘pi bilan 8 ta rasm yuklash mumkin.',
                    ],
                ],
            ], 422);
        }

        // Oddiy maydonlar
        $gift->fill($request->only(['name']));

        // FILIAL STOCK: jami stock service orqali yangilanadi
        app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
            'gift', (int) $gift->id, 0, (int) $gift->seller_id,
            (int) $request->stock,
            $seller->seller_location_id ? (int) $seller->seller_location_id : null,
            ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Sovg\'a tahriri']
        );
        $gift->artikul = $gift->artikul ?: ProductArtikul::generate('gift', (int) $gift->id);
        $gift->priceFrom = (int) $request->min_price;
        $gift->priceTo = (int) $request->max_price;

        // Rasmlar
        // O'chirilganlarni serverdan o'chirish
        foreach ($currentImages as $path) {
            if (!in_array($path, $existingImages) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                ProductImageVariantGenerator::deleteForPath($path);
            }
        }

        // Yangi rasmlarni yuklash
        $newImages = $request->hasFile('images') ? $this->uploadImages($request->file('images')) : [];

        // Yakuniy array
        $gift->images = array_values(array_merge($existingImages, $newImages));

        $gift->save();

        $this->writeLog($seller, 'Updated gift', "Gift ID: {$gift->id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift yangilandi',
            'gift'    => $gift
        ], 200);
    }

    private function uploadImages($files)
    {
        $paths = [];
        foreach ($files as $file) {
            $path = $file->store('gifts', 'public');
            $paths[] = $path;
            ProductImageVariantGenerator::generateForPath($path);
        }
        return $paths;
    }
    /**
     * Gift o'chirish (ixtiyoriy — agar kerak bo'lsa qo'shishingiz mumkin)
     */
    public function delete(Request $request, $id)
    {
        $seller = Auth::guard('seller')->user();

        if (!$this->hasGiftAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $gift = Gifts::where('id', $id)
            ->where('seller_id', $storeSellerId)
            ->whereNull('archived_at')
            ->first();

        if (!$gift) {
            return response()->json(['success' => false, 'message' => 'Gift not found'], 404);
        }

        $isUsedInOrders = DB::table('seller_order_items')
            ->where('type', 'gift')
            ->where('product_id', $gift->id)
            ->exists();

        if ($isUsedInOrders) {
            $gift->update([
                'status' => false,
                'archived_at' => now(),
            ]);

            app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                'gift', (int) $gift->id, 0, (int) $gift->seller_id, 0, null,
                ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Sovg\'a arxivlandi']
            );

            return response()->json([
                'success' => true,
                'message' => 'Buyurtmalar tarixini saqlash uchun sovg‘a o‘chirilmay, nofaol qilindi.',
                'archived' => true,
            ]);
        }

        foreach (($gift->images ?? []) as $path) {
            $storagePath = $this->storagePath($path);
            if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
                ProductImageVariantGenerator::deleteForPath($storagePath);
            }
        }

        $gift->delete();

        $this->writeLog($seller, 'Deleted gift', "Gift ID: {$id}");

        return response()->json([
            'success' => true,
            'message' => 'Gift muvaffaqiyatli o\'chirildi'
        ], 200);
    }

    private function storagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH) ?: $path;
        return ltrim(preg_replace('#^/storage/#', '', $parsedPath), '/');
    }
}
