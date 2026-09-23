<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Books;
use App\Models\BookCategories;
use App\Models\Publisher;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\StatyioneryTag;
use App\Models\StatyioneryVariant;
use App\Models\Seller;
use App\Models\BookTag;
use App\Models\Sold;
use App\Models\Gifts;
use App\Models\SellerOrderItem;
use App\Models\SellerStaffLog;
use App\Services\AuthorDirectoryService;
use App\Services\PublisherDirectoryService;
use App\Support\ProductImageVariantGenerator;
use App\Support\ProductArtikul;
use App\Services\SellerPremiumService;
use App\Services\ProductModerationStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected \App\Services\OpenAIService $ai,
        protected SellerPremiumService $premiumService,
        protected AuthorDirectoryService $authorDirectory,
        protected PublisherDirectoryService $publisherDirectory,
        protected ProductModerationStateService $productModerationState,
    )
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    protected function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ PRODUCT ACCESS: OWNER, ADMIN (1), PRODUCT MANAGER (2)
     */
    protected function hasProductAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 yoki 2 → ACCESS
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    /**
     * ✅ LOG YOZISH (FAQAT AMAL UCHUN)
     */
    protected function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
    }

    protected function assignGeneratedArtikul($product, string $type): void
    {
        if ($product->artikul) {
            return;
        }

        $product->forceFill([
            'artikul' => ProductArtikul::generate($type, (int) $product->id),
        ])->save();
    }

    public function lastProducts(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK (LOG YO'Q - FAQAT KO'RISH)
        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Products available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $books = Books::query()->where('seller_id', $storeSellerId)
            ->where('is_hidden', false)
            ->with(['category', 'tags', 'publisher:id,name'])
            ->latest('updated_at')
            ->limit(250)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books,
        ], 200);
    }
    
    /**
 * Sellerning kitoblari va kanselyariya mahsulotlarini pagination bilan qaytarish
 */
public function lastProductsBS(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied.'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);

    $booksPage = max(1, $request->input('books_page', 1));
    $stationeryPage = max(1, $request->input('stationery_page', 1));
    $perPage = $request->input('per_page', 20);

    $booksFilter = $request->input('books_filter', 'all');
    $stationeryFilter = $request->input('stationery_filter', 'all');

    // YANGI: alohida search parametrlari
    $booksSearch = $request->input('books_search', '');
    $stationerySearch = $request->input('stationery_search', '');

    $bookBase = Books::query()->where('seller_id', $storeSellerId)->where('is_hidden', false);
    $stationeryBase = Seller::find($storeSellerId)->stationeries()->where('is_hidden', false);

    // COUNTS (paginationdan mustaqil, serverdagi aniq sonlar)
    $booksCounts = [
        'all' => (clone $bookBase)->count(),
        'active' => (clone $bookBase)->where('is_approved', 1)->whereStockAvailable('>', 3)->count(),
        'out_stock' => (clone $bookBase)->whereStockAvailable('<=', 0)->count(),
        'low_stock' => (clone $bookBase)->where('is_approved', 1)->stockBetween(1, 3)->count(),
        'pending' => (clone $bookBase)->where(fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0))->count(),
        'rejected' => (clone $bookBase)->where('is_approved', 2)->count(),
    ];

    $stationeryCounts = [
        'all' => (clone $stationeryBase)->count(),
        'active' => (clone $stationeryBase)->where('is_approved', 1)->whereStockAvailable('>', 3)->count(),
        'out_stock' => (clone $stationeryBase)->whereStockAvailable('<=', 0)->count(),
        'low_stock' => (clone $stationeryBase)->where('is_approved', 1)->stockBetween(1, 3)->count(),
        'pending' => (clone $stationeryBase)->where(fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0))->count(),
        'rejected' => (clone $stationeryBase)->where('is_approved', 2)->count(),
    ];

    // Asosiy querylar
    $booksQuery = Books::query()->where('seller_id', $storeSellerId)
        ->withAvailableTotal()
        ->where('is_hidden', false)
        ->with(['category', 'tags', 'publisher:id,name']);

    $stationeryQuery = Seller::find($storeSellerId)->stationeries()
        ->withAvailableTotal()
        ->where('is_hidden', false)
        ->with(['category', 'variants', 'tags']);

    // Books search
    if ($booksSearch !== '') {
        $booksQuery->where(function ($query) use ($booksSearch) {
            $query->where('name', 'like', "%{$booksSearch}%")
                ->orWhere('artikul', 'like', "%{$booksSearch}%")
                ->orWhere('isbn', 'like', "%{$booksSearch}%")
                ->orWhere('author', 'like', "%{$booksSearch}%");
        });
    }

    // Stationery search
    if ($stationerySearch !== '') {
        $stationeryQuery->where(function ($query) use ($stationerySearch) {
            $query->where('name', 'like', "%{$stationerySearch}%")
                ->orWhere('artikul', 'like', "%{$stationerySearch}%")
                ->orWhere('barcode', 'like', "%{$stationerySearch}%");
        });
    }

    // Books filter
    if ($booksFilter === 'active') {
        $booksQuery->where('is_approved', 1)->whereStockAvailable('>', 3);
    } elseif ($booksFilter === 'out_stock') {
        $booksQuery->whereStockAvailable('<=', 0);
    } elseif ($booksFilter === 'low_stock') {
        $booksQuery->where('is_approved', 1)->stockBetween(1, 3);
    } elseif ($booksFilter === 'pending') {
        $booksQuery->where(fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0));
    } elseif ($booksFilter === 'rejected') {
        $booksQuery->where('is_approved', 2);
    }

    // Stationery filter
    if ($stationeryFilter === 'active') {
        $stationeryQuery->where('is_approved', 1)->whereStockAvailable('>', 3);
    } elseif ($stationeryFilter === 'out_stock') {
        $stationeryQuery->whereStockAvailable('<=', 0);
    } elseif ($stationeryFilter === 'low_stock') {
        $stationeryQuery->where('is_approved', 1)->stockBetween(1, 3);
    } elseif ($stationeryFilter === 'pending') {
        $stationeryQuery->where(fn ($query) => $query->whereNull('is_approved')->orWhere('is_approved', 0));
    } elseif ($stationeryFilter === 'rejected') {
        $stationeryQuery->where('is_approved', 2);
    }

    $books = $booksQuery->latest('updated_at')->paginate($perPage, ['*'], 'books_page', $booksPage);
    $stationery = $stationeryQuery->latest('updated_at')->paginate($perPage, ['*'], 'stationery_page', $stationeryPage);

    return response()->json([
        'success' => true,
        'data' => [
            'books' => [
                'data' => $books->items(),
                'total' => $books->total(),
                'has_more' => $books->hasMorePages(),
            ],
            'stationery' => [
                'data' => $stationery->items(),
                'total' => $stationery->total(),
                'has_more' => $stationery->hasMorePages(),
            ],
        ],
        'counts' => [
            'books' => $booksCounts,
            'stationery' => $stationeryCounts,
        ],
    ], 200);
}

    public function productsCount(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK (LOG YO'Q - FAQAT KO'RISH)
        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => true,
                'products' => 0,
            ], 200);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $count = Books::query()->where('seller_id', $storeSellerId)
            ->where('is_hidden', false)
            ->count();

        return response()->json([
            'success' => true,
            'products' => $count,
        ], 200);
    }

    public function getAuthorSuggestions(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $authors = Author::query()
            ->select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Author $author) => [
                'id' => $author->id,
                'name' => $author->name,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $authors,
        ], 200);
    }

    public function getPublisherSuggestions(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            return response()->json(['success' => true, 'data' => []], 200);
        }

        $publishers = Publisher::query()
            ->select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Publisher $publisher) => [
                'id' => $publisher->id,
                'name' => $publisher->name,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $publishers,
        ], 200);
    }
    
    /**
 * ✅ CREATE STATIONERY PRODUCT - LOG YOZILADI
 */
public function createStationery(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);

    // 1. Taglarni integerga o'girib olish (Validation xatosi bermasligi uchun)
    if ($request->has('tag_ids')) {
        $request->merge([
            'tag_ids' => array_map('intval', (array)$request->tag_ids)
        ]);
    }

    // 2. Validatsiya
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'barcode' => 'nullable|string|max:32',
        'material' => 'nullable|string|max:255',
        'price' => 'required|numeric|min:0',
        'discountPrice' => 'nullable|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'description' => 'required|string',
        'category_id' => 'required|integer|exists:stationery_categories,id',
        'tag_ids' => 'nullable|array',
        'tag_ids.*' => 'integer|exists:stationery_tags,id',
        // Variantlar uchun qat'iy tekshiruvni olib tashlaymiz yoki nullable qilamiz
        'variant_colors' => 'nullable|array',
        'variant_colors.*.color_name' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    // 3. Asosiy rasmlarni yuklash
    $imagePaths = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $index => $image) {
            $filename = time() . "_main_{$index}." . $image->getClientOriginalExtension();
            $path = $image->storeAs('stationery', $filename, 'public');
            $imagePaths[] = $path;
            ProductImageVariantGenerator::generateForPath($path);
        }
    }

    $normalizedBarcode = $this->normalizeBarcode($request->input('barcode'));

    // 4. Mahsulotni yaratish
    $stationery = Stationery::create([
        'seller_id'      => $storeSellerId,
        'category_id'    => $request->category_id,
        'name'           => $request->name,
        'barcode'        => $normalizedBarcode,
        'material'       => $request->material,
        'price'          => $request->price,
        'discount_price' => $request->discountPrice ?? 0,
        'description'    => $request->description,
        'images'         => $imagePaths,
        'is_approved'    => 0,
    ]);
    $this->assignGeneratedArtikul($stationery, 'stationery');

    // FILIAL STOCK: kirim hodim filialiga (bo'lmasa asosiy filialga)
    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
        'stationery', (int) $stationery->id, 0, (int) $storeSellerId,
        (int) $request->stock,
        $seller->seller_location_id ? (int) $seller->seller_location_id : null,
        ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Mahsulot yaratildi']
    );

    // 5. Taglarni bog'lash
    if ($request->filled('tag_ids')) {
        $stationery->tags()->sync($request->tag_ids);
    }

    // 6. Variantlarni saqlash (Siz so'ragan ESKI uslubda)
    if ($request->has('variant_colors')) {
        $variantInputs = $request->input('variant_colors');

        foreach ($variantInputs as $index => $variantItem) {
            // Rang nomi massivda yoki stringda kelishiga qarab olish
            $colorName = is_array($variantItem) ? ($variantItem['color_name'] ?? null) : $variantItem;

            if (empty($colorName)) continue;

            $variantImagePath = null;
            // Faylni borligini qat'iy tekshirish
            if ($request->hasFile("variant_colors.$index.image")) {
                $file = $request->file("variant_colors.$index.image");
                if ($file->isValid()) {
                    $filename = time() . "_var_" . uniqid() . '.' . $file->getClientOriginalExtension();
                    $variantImagePath = $file->storeAs('stationery/variants', $filename, 'public');
                    ProductImageVariantGenerator::generateForPath($variantImagePath);
                }
            }

            // Relationship orqali saqlash
            $stationery->variants()->create([
                'color_name' => $colorName,
                'image_path' => $variantImagePath,
            ]);
        }
    }

    $this->productModerationState->markPending($stationery, 'seller_created');
    $this->writeLog($seller, 'Yangi kanselyariya mahsuloti qo‘shdi (AI moderatsiyaga yuborildi)', $stationery->name);

    return response()->json([
        'success' => true,
        'message' => 'Mahsulot muvaffaqiyatli qo‘shildi',
    ], 201);
}

/**
 * ✅ UPDATE STATIONERY PRODUCT - LOG YOZILADI
 */
public function updateStationery(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Product update available only for Owner, Admin, and Product Manager.'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);

    // Validatsiya (Flutterdan kelgan nomlarga moslashtirildi)
    $validator = Validator::make($request->all(), [
        'id' => 'required|integer',
        'name' => 'required|string|max:255',
        'barcode' => 'nullable|string|max:32',
        'material' => 'nullable|string|max:255',
        'price' => 'required|numeric|min:0',
        'discountPrice' => 'nullable|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'description' => 'required|string',
        'category_id' => 'required|integer|exists:stationery_categories,id',
        'tag_ids' => 'nullable|array',
        'tag_ids.*' => 'integer|exists:stationery_tags,id',

        // Rasmlar
        'existing_images' => 'nullable|json',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:10240', // yangi asosiy rasmlar

        // Variantlar
        'variant_colors' => 'nullable|array',
        'variant_colors.*.id' => 'nullable|integer|exists:stationery_variants,id',
        'variant_colors.*.color_name' => 'required_with:variant_colors.*|string|max:100',
        'variant_colors.*.stock' => 'required_with:variant_colors.*|integer|min:0', // YANGI: stock majburiy
        'variant_colors.*.image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Mahsulotni topish
    $stationery = \App\Models\Stationery::where('seller_id', $storeSellerId)
        ->where('id', $request->id)
        ->firstOrFail();

    if (!$stationery) {
        return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
    }

    // === ASOSIY RASMLAR ===
    // === ASOSIY RASMLAR ===
$existingImages = $request->filled('existing_images')
    ? json_decode($request->existing_images, true)
    : [];

$currentImagesInDb = is_array($stationery->images) ? $stationery->images : [];

// === O'chirilgan rasmlarni avtomatik aniqlash ===
$deletedImages = array_diff($currentImagesInDb, $existingImages);

foreach ($deletedImages as $img) {
    Storage::disk('public')->delete($img);
    ProductImageVariantGenerator::deleteForPath($img);
}

// Tartiblangan existing rasmlar + yangi yuklanganlar
$finalImages = $existingImages;

if ($request->hasFile('images')) {
    foreach ($request->file('images') as $index => $image) {
        if ($image->isValid()) {
            $filename = time() . "_main_{$index}_" . uniqid() . "." . $image->getClientOriginalExtension();
            $path = $image->storeAs('stationery', $filename, 'public');
            $finalImages[] = $path;
            ProductImageVariantGenerator::generateForPath($path);
        }
    }
}

$finalImages = array_values(array_unique($finalImages));

if (empty($finalImages)) {
    return response()->json(['success' => false, 'message' => 'Kamida bitta rasm bo‘lishi kerak'], 422);
}

    // === O'CHIRILGAN VARIANTLAR ===
    // Flutterdan kelgan variantlar (id mavjud bo'lsa)
$incomingVariantIds = [];
if ($request->has('variant_colors')) {
    foreach ($request->variant_colors as $vData) {
        if (!empty($vData['id'])) {
            $incomingVariantIds[] = $vData['id'];
        }
    }
}

// Bazadagi barcha variantlar
$existingVariants = $stationery->variants()->pluck('id')->toArray();

// Bazadagi, lekin kelmagan variantlarni o'chirish
$variantsToDelete = array_diff($existingVariants, $incomingVariantIds);

\App\Models\StationeryVariant::whereIn('id', $variantsToDelete)
    ->get()
    ->each(function ($variant) {
        if ($variant->image_path) {
            Storage::disk('public')->delete($variant->image_path);
            ProductImageVariantGenerator::deleteForPath($variant->image_path);
        }
        $variant->delete();
    });

// O'chirilgan variantlarning filial stock qatorlarini tozalash
if (!empty($variantsToDelete)) {
    \App\Models\BranchStock::where('product_type', 'stationery')
        ->where('product_id', $stationery->id)
        ->whereIn('variant_id', $variantsToDelete)
        ->delete();
}


    // === VARIANTLARNI YANGILASH / YARATISH ===
    if ($request->has('variant_colors')) {
        foreach ($request->variant_colors as $index => $vData) {
            $variantImagePath = null;

            // Yangi variant rasmi yuklanganmi?
            if (isset($vData['image']) && $vData['image'] instanceof \Illuminate\Http\UploadedFile) {
    $vFile = $vData['image'];
    $vFilename = time() . "_var_" . uniqid() . "." . $vFile->getClientOriginalExtension();
    $variantImagePath = $vFile->storeAs('stationery/variants', $vFilename, 'public');
    ProductImageVariantGenerator::generateForPath($variantImagePath);
}


            if (isset($vData['id']) && !empty($vData['id'])) {
                // Eskisini yangilash
                $variant = $stationery->variants()->find($vData['id']);
                if ($variant) {
                    $updateData = [
                        'color_name' => $vData['color_name'],
                    ];

                    // FILIAL STOCK: variant stock service orqali
                    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                        'stationery', (int) $stationery->id, (int) $variant->id, (int) $storeSellerId,
                        (int) ($vData['stock'] ?? 0),
                        $seller->seller_location_id ? (int) $seller->seller_location_id : null,
                        ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Variant tahriri']
                    );

                    if ($variantImagePath) {
                        if ($variant->image_path) {
                            Storage::disk('public')->delete($variant->image_path);
                            ProductImageVariantGenerator::deleteForPath($variant->image_path);
                        }
                        $updateData['image_path'] = $variantImagePath;
                    }

                    $variant->update($updateData);
                }
            } else {
                // Yangi variant yaratish
                $newVariant = $stationery->variants()->create([
                    'color_name' => $vData['color_name'],
                    'image_path' => $variantImagePath,
                ]);

                app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                    'stationery', (int) $stationery->id, (int) $newVariant->id, (int) $storeSellerId,
                    (int) ($vData['stock'] ?? 0),
                    $seller->seller_location_id ? (int) $seller->seller_location_id : null,
                    ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Variant yaratildi']
                );
            }
        }
    }

    // === TAGLARNI SYNC QILISH ===
    $tagIds = $request->input('tag_ids', []);
    $stationery->tags()->sync($tagIds);

    $normalizedBarcode = $this->normalizeBarcode($request->input('barcode'));
    // === ASOSIY MA'LUMOTLARNI YANGILASH ===
    $stationery->update([
        'name' => $request->name,
        'artikul' => $stationery->artikul ?: ProductArtikul::generate('stationery', (int) $stationery->id),
        'barcode' => $normalizedBarcode,
        'material' => $request->material ?? $stationery->material,
        'price' => $request->price,
        'discount_price' => $request->discountPrice ?? 0,
        'description' => $request->description,
        'images' => $finalImages,
        'category_id' => $request->category_id,
    ]);
    // MUHIM: `is_approved` va AI moderatsiya holati BU YERDA majburan
    // qayta o'rnatilmaydi — `ProductModerationObserver::updating()` buni
    // avtomatik va TO'G'RI qiladi: faqat moderatsiyaga aloqador maydonlar
    // (nom, narx, rasm, tavsif va h.k.) haqiqatan o'zgargan bo'lsagina
    // mahsulot qayta AI ko'rib chiqishiga yuboriladi. Aks holda (masalan
    // faqat stock yangilangan yoki forma o'zgarishsiz qayta yuborilgan
    // bo'lsa) allaqachon faol/tasdiqlangan mahsulot bekorga qayta
    // moderatsiyaga tushib qolmaydi.

    // FILIAL STOCK: mahsulot darajasidagi stock service orqali
    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
        'stationery', (int) $stationery->id, 0, (int) $storeSellerId,
        (int) $request->stock,
        $seller->seller_location_id ? (int) $seller->seller_location_id : null,
        ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Mahsulot tahriri']
    );

    $this->writeLog($seller, 'Kanselyariya mahsulotini tahrirladi', $stationery->name);

    return response()->json([
        'success' => true,
        'message' => 'Mahsulot muvaffaqiyatli yangilandi',
        'data' => $stationery->fresh()->load(['category', 'variants', 'tags']),
    ], 200);
}

    /**
 * ✅ DELETE PRODUCT (Book or Stationery) - LOG YOZILADI
 */
public function removeProduct(Request $request)
{
    $seller = Auth::guard('seller')->user();
    $productId = $request->input('product_id');
    $type = $request->input('type'); // 'book' yoki 'stationery'

    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false, 
            'message' => 'Access denied. Product management available only for Owner, Admin, and Product Manager.'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);
    $product = null;

    // Type bo'yicha modelni tanlash
    if ($type === 'stationery') {
        $product = \App\Models\Stationery::where('seller_id', $storeSellerId)->find($productId);
    } else {
        // Default bo'lib 'book' deb hisoblaymiz (eski kod saqlanib qolishi uchun)
        $product = \App\Models\Books::query()->where('seller_id', $storeSellerId)->find($productId);
    }

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    // LOG YOZISH
    $prefix = match ($type) {
        'stationery' => '[Kanselyariya] ',
        'gift' => '[Sovg‘a] ',
        default => '[Kitob] ',
    };
    $this->writeLog($seller, 'Maxsulotni marketdan o\'chirib yubordi', $prefix . $product->name);

    // O'chirish (Hiden qilish)
    $product->is_hidden = true;
    $product->save();

    return response()->json(['success' => true, 'message' => 'Product removed successfully'], 200);
}

/**
 * ✅ UPDATE STATUS (Book or Stationery) - LOG YOZILADI
 */
public function updateProductStatus(Request $request)
{
    $seller = Auth::guard('seller')->user();
    $productId = $request->input('product_id');
    $type = $request->input('type'); // 'book' yoki 'stationery'

    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false, 
            'message' => 'Access denied. Product management available only for Owner, Admin, and Product Manager.'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);
    $product = null;

    // Type bo'yicha modelni tanlash
    if ($type === 'stationery') {
        $product = \App\Models\Stationery::where('seller_id', $storeSellerId)->find($productId);
    }elseif ($type === 'gift') {
        $product = Gifts::where('seller_id', $storeSellerId)
            ->whereNull('archived_at')
            ->find($productId);
    } else {
        $product = \App\Models\Books::query()->where('seller_id', $storeSellerId)->find($productId);
    }

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    $oldStatus = $product->status ? 'Faol' : 'Nofaol';
    $newStatus = !$product->status ? 'Faol' : 'Nofaol';
    $prefix = match ($type) {
        'stationery' => '[Kanselyariya] ',
        'gift' => '[Sovg‘a] ',
        default => '[Kitob] ',
    };

    // LOG YOZISH
    $this->writeLog($seller, 'Maxsulot holatini o\'zgartirdi', "{$prefix}{$product->name} | {$oldStatus} → {$newStatus}");

    // Statusni almashtirish
    $product->status = !$product->status;
    $product->save();

    return response()->json(['success' => true, 'status' => $product->status], 200);
}

    /**
     * ✅ CREATE PRODUCT - LOG YOZILADI
     */
    public function createProduct(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // ✅ ACCESS CHECK
        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Product creation available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);

        $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'translator' => 'nullable|string|max:255',
            // MUHIM: nashriyot endi muallif kabi ERKIN matn — ro'yxatdan
            // aynan bittasini tanlash MAJBURIY emas. Mos keladigan yozuv
            // topilmasa, `PublisherDirectoryService` yangi nashriyotni
            // o'zi yaratadi (pastda, `resolveOrCreateByName`).
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'required|integer|min:1',
            'language' => 'required|string|in:uz,ru,en,qq',
            'languageWrite' => 'required|string|in:cyrillic,latin',
            'coverType' => 'required|string|in:soft,hard',
            'price' => 'required|numeric|min:0',
            'discountPrice' => 'nullable|numeric|min:0',
            'count' => 'required|integer|min:0',
            'description' => 'required|string',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'category_id' => 'required|integer|exists:book_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:book_tags,id',
            'year' => 'nullable|integer|min:1800|max:' . (now()->year + 1),
            'discountExpiresAt' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ISBN'ni kanonik shaklga keltiramiz; noto'g'ri formatda kelsa null
        // saqlanadi (ISBN'siz odatdagidek admin tasdig'i kutiladi).
        $canonicalIsbn = Books::normalizeIsbn($request->input('isbn'));

        // Image upload (qisqartirildi)
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                if ($image->isValid()) {
                    $filename = time() . '_' . $index . '.' . $image->getClientOriginalExtension();
                    $path = Storage::disk('public')->putFileAs('books', $image, $filename);
                    $normalizedPath = str_replace('public/', '', $path);
                    $imagePaths[] = $normalizedPath;
                    ProductImageVariantGenerator::generateForPath($normalizedPath);
                }
            }
        }

        $author = $this->authorDirectory->resolveOrCreateByName($request->input('author'));
        $publisher = $this->publisherDirectory->resolveOrCreateByName($request->input('publisher'));

        $book = Books::create([
            'seller_id' => $storeSellerId,
            'name' => $request->input('name'),
            'author' => $author?->name ?: $request->input('author'),
            'author_id' => $author?->id,
            'translator' => $request->input('translator'),
            'publisher_id' => $publisher?->id,
            'isbn' => $canonicalIsbn,
            'pages' => $request->input('pages'),
            'lang' => $request->input('language'),
            'langType' => $request->input('languageWrite'),
            'coverType' => $request->input('coverType'),
            'price' => $request->input('price'),
            'discountPrice' => $request->input('discountPrice', 0),
            'discountExpiresAt' => $request->filled('discountExpiresAt') ? $request->input('discountExpiresAt') : null,
            'description' => $request->input('description'),
            'images' => $imagePaths,
            'year' => (int) ($request->input('year') ?: now()->year),
            'category_id' => $request->input('category_id'),
            'status' => true,
            'is_hidden' => false,
            'is_approved' => 0,
        ]);
        $this->assignGeneratedArtikul($book, 'book');

        // FILIAL STOCK: kirim hodim filialiga (bo'lmasa asosiy filialga)
        app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
            'book', (int) $book->id, 0, (int) $storeSellerId,
            (int) $request->input('count'),
            $seller->seller_location_id ? (int) $seller->seller_location_id : null,
            ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Mahsulot yaratildi']
        );

        if ($request->has('tag_ids')) {
            $book->tags()->attach($request->input('tag_ids'));
        }

        $this->productModerationState->markPending($book, 'seller_created');
        $this->writeLog($seller, 'Yangi maxsulot qo\'shdi (AI moderatsiyaga yuborildi)', $book->name);

        // fresh(): taklif global kartaga ulangan bo'lsa (avto-ulash) ma'lumot
        // kartadan qayta yozilgan — ilovaga bazadagi HAQIQIY holat qaytariladi.
        $book = $book->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $book->load(['category', 'tags']),
            'catalog_locked' => (bool) $book->edition_id,
        ], 201);
    }

    public function updateProduct(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    // ACCESS CHECK
    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Product update available only for Owner, Admin, and Product Manager.'
        ], 403);
    }

    $storeSellerId = $this->getStoreSellerId($seller);

    // GLOBAL KATALOG: ulangan taklifda ilova faqat narx/qoldiq yuboradi —
    // kitob maydonlarini (nom, muallif, tavsif…) talab qilmaymiz.
    $linked = Books::query()
        ->where('seller_id', $storeSellerId)
        ->whereKey($request->input('id'))
        ->whereNotNull('edition_id')
        ->exists();

    $validator = Validator::make($request->all(), $linked ? [
        'id' => 'required|integer',
        'price' => 'required|numeric|min:1|max:100000000',
        'discountPrice' => 'nullable|numeric|min:0|lt:price',
        'count' => 'required|integer|min:0|max:100000',
        'discountExpiresAt' => 'nullable|date',
    ] : [
        'id' => 'required|integer',
        'name' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'translator' => 'nullable|string|max:255',
        'publisher' => 'nullable|string|max:255',
        'isbn' => 'nullable|string|max:20',
        'pages' => 'required|integer|min:1',
        'language' => 'required|string|in:uz,ru,en,qq',
        'languageWrite' => 'required|string|in:cyrillic,latin',
        'coverType' => 'required|string|in:soft,hard',
        'price' => 'required|numeric|min:0',
        'discountPrice' => 'nullable|numeric|min:0',
        'count' => 'required|integer|min:0',
        'description' => 'required|string',
        'existingImages' => 'nullable|json',
        'deletedImages' => 'nullable|json',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        'category_id' => 'required|integer|exists:book_categories,id',
        'tag_ids' => 'nullable|array',
        'tag_ids.*' => 'integer|exists:book_tags,id',
        'discountExpiresAt' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $product = Books::query()->where('seller_id', $storeSellerId)->find($request->id);
    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
    }

    // Kitobning O'ZINIKI bo'lgan ma'lumoti (nom, muallif, rasm, tavsif) KATALOGNIKI —
    // ulangan taklifda do'kon faqat narx, chegirma va qoldiqni boshqaradi. Kartada
    // xato bo'lsa: POST catalog/editions/{id}/correction (tuzatish taklifi).
    if ($product->edition_id) {
        $product->update([
            'artikul' => $product->artikul ?: ProductArtikul::generate('book', (int) $product->id),
            'price' => $request->price,
            'discountPrice' => $request->discountPrice ?? 0,
            'discountExpiresAt' => $request->filled('discountExpiresAt') ? $request->discountExpiresAt : null,
        ]);
        app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
            'book', (int) $product->id, 0, (int) $storeSellerId,
            (int) $request->count,
            $seller->seller_location_id ? (int) $seller->seller_location_id : null,
            ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Mahsulot tahriri']
        );
        $this->writeLog($seller, 'Katalog kitobining narx/qoldig\'ini yangiladi', $product->name);

        return response()->json([
            'success' => true,
            'message' => "Narx va qoldiq yangilandi. Kitob ma'lumotlari (nom, muallif, muqova, tavsif) umumiy katalogdan olinadi — tuzatish kerak bo'lsa \"Tuzatish taklif qilish\" orqali yuboring.",
            'catalog_locked' => true,
            'data' => $product->fresh()->load(['category', 'tags']),
        ], 200);
    }

    // existingImages va deletedImages ni to'g'ri olish
    $existingImages = $request->filled('existingImages')
        ? json_decode($request->existingImages, true)
        : [];

    $deletedImages = $request->filled('deletedImages')
        ? json_decode($request->deletedImages, true)
        : [];

    if (!is_array($existingImages) || !is_array($deletedImages)) {
        return response()->json([
            'success' => false,
            'message' => 'existingImages va deletedImages JSON massiv bo‘lishi kerak',
        ], 422);
    }

    // Joriy bazadagi rasmlar (o'chirish uchun tekshirish)
    $currentImages = is_string($product->images)
        ? json_decode($product->images, true) ?? []
        : ($product->images ?? []);

    // O'chirilgan rasmlarni diskdan o'chirish
    foreach ($deletedImages as $image) {
        if (is_string($image) && in_array($image, $currentImages) && \App\Support\SharedImageGuard::canDelete($image, (int) $product->id)) {
            try {
                Storage::disk('public')->delete($image);
                ProductImageVariantGenerator::deleteForPath($image);
            } catch (\Exception $e) {
                Log::error('Rasm o‘chirishda xato', [
                    'image' => $image,
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    // Saqlanadigan eski rasmlar (foydalanuvchi tartibida!)
    $finalImages = $existingImages; // Bu tartibni saqlaymiz!

    // Yangi yuklangan rasmlarni qo'shish (oxiriga, chunki reorderda ular oldin qo'yilgan bo'ladi)
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $index => $image) {
            if ($image->isValid()) {
                try {
                    $filename = time() . "_{$index}." . $image->getClientOriginalExtension();
                    $path = $image->storeAs('books', $filename, 'public'); // books/filename.jpg
                    $finalImages[] = $path; // oxiriga qo'shamiz
                    ProductImageVariantGenerator::generateForPath($path);
                } catch (\Exception $e) {
                    Log::error('Yangi rasm saqlashda xato', [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    // Takrorlanishlarni olib tashlash (agar xato bo'lsa)
    $finalImages = array_values(array_unique($finalImages));

    // Agar hech qanday rasm qolmagan bo'lsa — xato
    if (empty($finalImages)) {
        return response()->json([
            'success' => false,
            'message' => 'Kamida bitta rasm bo‘lishi kerak'
        ], 422);
    }

    $canonicalIsbn = Books::normalizeIsbn($request->input('isbn'));
    $author = $this->authorDirectory->resolveOrCreateByName($request->author);
    $publisher = $this->publisherDirectory->resolveOrCreateByName($request->input('publisher'));

    // Mahsulotni yangilash
    $product->update([
        'name' => $request->name,
        'artikul' => $product->artikul ?: ProductArtikul::generate('book', (int) $product->id),
        'author' => $author?->name ?: $request->author,
        'author_id' => $author?->id,
        'translator' => $request->translator,
        'publisher_id' => $publisher?->id ?? ($request->filled('publisher') ? null : $product->publisher_id), // tez tahrir nashriyotni o'chirib yubormasin
        'isbn' => $canonicalIsbn,
        'pages' => $request->pages,
        'lang' => $request->language,
        'langType' => $request->languageWrite,
        'coverType' => $request->coverType,
        'price' => $request->price,
        'discountPrice' => $request->discountPrice ?? 0,
        'discountExpiresAt' => $request->filled('discountExpiresAt') ? $request->discountExpiresAt : null,
        'description' => $request->description,
        'images' => $finalImages,
        'category_id' => $request->category_id,
    ]);
    // MUHIM: yuqoridagi izohga qarang (stationery tahriri) — `is_approved`
    // va AI moderatsiya holati bu yerda ham majburan qayta o'rnatilmaydi,
    // `ProductModerationObserver` moderatsiyaga aloqador maydonlar
    // haqiqatan o'zgarganda buni o'zi to'g'ri bajaradi.

    // FILIAL STOCK: jami stock yangi songa keltiriladi (farq hodim filialiga)
    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
        'book', (int) $product->id, 0, (int) $storeSellerId,
        (int) $request->count,
        $seller->seller_location_id ? (int) $seller->seller_location_id : null,
        ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Mahsulot tahriri']
    );

    $product->tags()->sync($request->input('tag_ids', []));

    $this->writeLog($seller, 'Mahsulot ma\'lumotlarini yangiladi', $product->name);
    return response()->json([
        'success' => true,
        'message' => 'Mahsulot muvaffaqiyatli yangilandi',
        'data' => $product->fresh()->load(['category', 'tags']),
    ], 200);
}

    /**
 * ✅ PRODUCT STATISTICS (Book or Stationery)
 */
public function productStatistics(Request $request, $id)
{
    $seller = Auth::guard('seller')->user();
    $type = $request->query('type', 'book'); // Default: 'book'

    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false, 
            'message' => 'Access denied.'
        ], 403);
    }

    if (!in_array($type, ['book', 'stationery', 'gift'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Mahsulot turi noto‘g‘ri.',
        ], 422);
    }

    $storeSellerId = $this->getStoreSellerId($seller);
    $product = null;

    // 1. Turni aniqlash va mahsulotni topish
    if ($type === 'stationery') {
        $product = \App\Models\Stationery::where('seller_id', $storeSellerId)
            ->where('id', $id)
            ->first();
    }elseif ($type === 'gift') {
        $product = \App\Models\Gifts::where('seller_id', $storeSellerId)
            ->whereNull('archived_at')
            ->where('id', $id)
            ->first();
    } else {
        $product = \App\Models\Books::where('seller_id', $storeSellerId)
            ->where('id', $id)
            ->first();
    }

    if (!$product) {
        return response()->json([
            'success' => false, 
            'message' => ($type === 'stationery' ? 'Mahsulot' : 'Kitob') . ' topilmadi'
        ], 404);
    }

    $completedItems = SellerOrderItem::query()
        ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
        ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
        ->where('seller_order_items.seller_id', $storeSellerId)
        ->where('seller_order_items.product_id', $product->id)
        ->where('seller_order_items.type', $type)
        ->whereNotNull('solds.completed_at')
        ->where(function ($query) {
            $query->where('solds.status_code', OrderStatusCode::CUSTOMER_RECEIVED->value)
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('solds.status_code')
                        ->where('solds.status', OrderStatusCode::CUSTOMER_RECEIVED->legacy());
                })
                ->orWhere(function ($delivered) {
                    $delivered->where(function ($status) {
                        $status->where('solds.status_code', OrderStatusCode::DELIVERED->value)
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('solds.status_code')
                                    ->where('solds.status', OrderStatusCode::DELIVERED->legacy());
                            });
                    })->whereRaw(
                        "LOWER(COALESCE(solds.deliveryType, 'delivery')) NOT IN (?, ?, ?)",
                        ['postal', 'mail_service', 'uzpost']
                    )->whereRaw(
                        "LOWER(COALESCE(solds.deliveryType, 'delivery')) NOT LIKE ?",
                        ['%pochta%']
                    )->whereRaw(
                        "LOWER(COALESCE(solds.deliveryType, 'delivery')) NOT LIKE ?",
                        ['%mail%']
                    )->whereRaw(
                        "LOWER(COALESCE(solds.deliveryType, 'delivery')) NOT LIKE ?",
                        ['%post%']
                    );
            });
        })
        ->where(function ($query) {
            $query->where('solds.payment_status_code', PaymentStatusCode::PAID->value)
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('solds.payment_status_code')
                        ->where('solds.paymentStatus', PaymentStatusCode::PAID->legacy());
                });
        });

    if (Schema::hasColumn('seller_order_items', 'cancelled_at')) {
        $completedItems->whereNull('seller_order_items.cancelled_at');
    }

    $totals = (clone $completedItems)
        ->selectRaw('COALESCE(SUM(seller_order_items.quantity), 0) as total_sales')
        ->selectRaw('COALESCE(SUM(seller_order_items.price * seller_order_items.quantity), 0) as total_revenue')
        ->selectRaw('COUNT(DISTINCT COALESCE(solds.user_id, seller_orders.client_id)) as total_clients')
        ->first();

    $weekTotals = (clone $completedItems)
        ->where('solds.completed_at', '>=', now()->subDays(6)->startOfDay())
        ->selectRaw('COALESCE(SUM(seller_order_items.quantity), 0) as total_sales')
        ->selectRaw('COALESCE(SUM(seller_order_items.price * seller_order_items.quantity), 0) as total_revenue')
        ->selectRaw('COUNT(DISTINCT COALESCE(solds.user_id, seller_orders.client_id)) as total_clients')
        ->first();

    $result = [
        'id'             => $product->id,
        'name'           => $product->name,
        'type'           => $type,
        'category_id'    => $product->category_id,
        
        // Umumiy statistikalar
        'total_sales'    => (int) ($totals->total_sales ?? 0),
        'total_revenue'  => (float) ($totals->total_revenue ?? 0),
        'total_clients'  => (int) ($totals->total_clients ?? 0),
        
        // Haftalik statistikalar
        'total_sales_week'   => (int) ($weekTotals->total_sales ?? 0),
        'total_clients_week' => (int) ($weekTotals->total_clients ?? 0),
        'total_revenue_week' => (float) ($weekTotals->total_revenue ?? 0),
        
        // Narx va Ombor
        'price'          => $product->price,
        'discount_price' => $product->discountPrice ?? $product->discount_price ?? 0,
        'current_stock'  => ($type === 'stationery') ? ($product->stock ?? 0) : ($product->count ?? 0),
    ];
    if ($type === 'book') {
        $result['author'] = $product->author;
    }

    return response()->json(['success' => true, 'data' => [$result]], 200);
}

    /**
     * ✅ ISBN AUTOFILL — barcode scannerdan keladigan ISBN bo'yicha
     * mavjud kitobni topib, formaga to'ldiriladigan maydonlarni qaytaradi.
     *
     * Mantiq:
     *  - is_approved = 1 va is_hidden = 0 bo'lgan qatorlardan eng so'nggi
     *    yangilangan variantni olamiz (har sellerda biroz farqli yozilgan
     *    bo'lishi mumkin — eng aktivi to'g'ri deb hisoblaymiz).
     *  - Image va description qaytarilmaydi (har seller o'zinikini qo'yadi).
     *  - language va languageWrite — Flutter forma kutadigan kalitlarga
     *    moslashtiriladi: "uz", "ru", "en", "qq" va "cyrillic"/"latin".
     *  - coverType: "soft"/"hard" ko'rinishida (DB "Yumshoq"/"Qattiq" yoki
     *    boshqa qiymat bo'lishi mumkin).
     */
    public function lookupByIsbn(Request $request, string $isbn)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $canonical = Books::normalizeIsbn($isbn);
        if ($canonical === null) {
            return response()->json([
                'success' => false,
                'message' => "ISBN formati noto'g'ri (10 yoki 13 raqam bo'lishi kerak).",
            ], 422);
        }

        // GLOBAL KATALOG: avval katalog kartalari (javob shakli o'zgarmaydi, qo'shimcha
        // `edition_id` va `images` maydonlari eski ilovaga zarar qilmaydi)
        $editions = app(\App\Services\Catalog\CatalogService::class)->findByIsbn($canonical, (int) $storeSellerId);
        if ($editions->isNotEmpty()) {
            $rows = $editions->map(fn ($edition) => $this->legacyIsbnPayload($edition, $canonical))->values();

            return $rows->count() > 1
                ? response()->json([
                    'success' => true,
                    'multiple_matches' => true,
                    'message' => "Bu ISBN bo'yicha bir nechta variant topildi. Kerakli kitobni tanlang.",
                    'candidates' => $rows,
                ])
                : response()->json(['success' => true, 'data' => $rows->first()]);
        }

        $books = Books::query()
            ->whereIsbn($canonical)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with(['tags:id', 'publisher:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        if ($books->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Bu ISBN Kitobchi bazasida topilmadi. Iltimos, ma'lumotlarni qo'lda to'ldiring.",
            ], 404);
        }

        if ($books->count() > 1) {
            return response()->json([
                'success'          => true,
                'multiple_matches' => true,
                'message'          => "Bu ISBN bo'yicha bir nechta variant topildi. Kerakli kitobni tanlang.",
                'candidates'       => $books->map(function ($book) use ($canonical) {
                    return [
                        'isbn'          => $canonical,
                        'name'          => $book->name,
                        'author'        => $book->author,
                        'translator'    => $book->translator,
                        'pages'         => (int) ($book->pages ?? 0),
                        'language'      => $this->normalizeLanguageOut($book->lang),
                        'languageWrite' => $this->normalizeLangTypeOut($book->langType),
                        'coverType'     => $this->normalizeCoverTypeOut($book->coverType),
                        'category_id'   => $book->category_id,
                        'publisher_id'  => $book->publisher_id,
                        'publisher_name'=> $book->publisher?->name,
                        'tag_ids'       => $book->tags->pluck('id')->values()->all(),
                        'year'          => (int) ($book->year ?? 0),
                    ];
                })->values(),
            ]);
        }

        $book = $books->first();

        return response()->json([
            'success' => true,
            'data' => [
                'isbn'          => $canonical,
                'name'          => $book->name,
                'author'        => $book->author,
                'translator'    => $book->translator,
                'pages'         => (int) ($book->pages ?? 0),
                'language'      => $this->normalizeLanguageOut($book->lang),
                'languageWrite' => $this->normalizeLangTypeOut($book->langType),
                'coverType'     => $this->normalizeCoverTypeOut($book->coverType),
                'category_id'   => $book->category_id,
                'publisher_id'  => $book->publisher_id,
                'publisher_name'=> $book->publisher?->name,
                'tag_ids'       => $book->tags->pluck('id')->values()->all(),
                'year'          => (int) ($book->year ?? 0),
            ],
        ]);
    }

    private function legacyIsbnPayload(\App\Models\BookEdition $edition, string $canonical): array
    {
        $edition->loadMissing('publisher:id,name');
        $urls = \App\Support\ProductImageUrls::build(array_values(array_filter((array) ($edition->images ?? []), 'is_string')));

        return [
            'isbn'          => $canonical,
            'name'          => $edition->title,
            'author'        => $edition->author,
            'translator'    => $edition->translator,
            'pages'         => (int) ($edition->pages ?? 0),
            'language'      => $this->normalizeLanguageOut($edition->lang),
            'languageWrite' => $this->normalizeLangTypeOut($edition->langType),
            'coverType'     => $this->normalizeCoverTypeOut($edition->coverType),
            'category_id'   => $edition->category_id,
            'publisher_id'  => $edition->publisher_id,
            'publisher_name'=> $edition->publisher?->name,
            'tag_ids'       => array_values((array) ($edition->tag_ids ?? [])),
            'year'          => (int) ($edition->year ?? 0),
            'edition_id'    => (int) $edition->id,
            'images'        => $urls['original'],
        ];
    }

    public function lookupStationeryByBarcode(Request $request, string $barcode)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $normalized = $this->normalizeBarcode($barcode);
        if ($normalized === null) {
            return response()->json([
                'success' => false,
                'message' => "Shtrix-kod formati noto'g'ri.",
            ], 422);
        }

        $stationery = Stationery::query()
            ->where('barcode', $normalized)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with('tags:id')
            ->orderByDesc('updated_at')
            ->first();

        if (!$stationery) {
            return response()->json([
                'success' => false,
                'message' => "Bu shtrix-kod bazada topilmadi. Mahsulot ma'lumotlarini qo'lda kiriting.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'barcode'     => $normalized,
                'name'        => $stationery->name,
                'material'    => $stationery->material,
                'category_id' => $stationery->category_id,
                'tag_ids'     => $stationery->tags->pluck('id')->values()->all(),
            ],
        ]);
    }

    /**
     * Do'kon uchun: ISBN (kitob) yoki shtrix-kod (kanselyariya) orqali do'kondagi
     * mahsulotni topib, zaxirasini yangilaydi.
     *   - stock: mutlaq yangi qiymat
     *   - delta: joriy zaxiraga qo'shiladigan o'zgarish (+/-)
     * Mahsulot saqlanganda observer webhook (product.stock_changed) yuboradi.
     */
    public function updateStockByCode(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->hasProductAccess($seller)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'type' => ['nullable', 'in:book,stationery'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'delta' => ['nullable', 'integer'],
        ]);

        $hasStock = array_key_exists('stock', $data) && $data['stock'] !== null;
        $hasDelta = array_key_exists('delta', $data) && $data['delta'] !== null;
        if (! $hasStock && ! $hasDelta) {
            return response()->json(['success' => false, 'message' => "stock yoki delta qiymatini yuboring."], 422);
        }

        $storeSellerId = $this->getStoreSellerId($seller);
        $code = trim($data['code']);
        $type = $data['type'] ?? null;

        // ── Kitob (ISBN) ──
        if ($type === 'book' || $type === null) {
            $canonical = Books::normalizeIsbn($code);
            if ($canonical !== null) {
                $book = Books::query()->where('seller_id', $storeSellerId)->whereIsbn($canonical)->first();
                if ($book) {
                    $new = $hasStock
                        ? (int) $data['stock']
                        : max(0, (int) $book->count + (int) $data['delta']);
                    // BUG TUZATILDI: `books.count` ustuni yo'q (filial zaxiralariga
                    // ko'chirilgan) — `$book->count = …; save()` jimgina hech narsa
                    // yozmasdi va javob "saqlandi" deb ko'rsatardi.
                    app(\App\Services\BranchStockService::class)->setTotalFromLegacy(
                        'book', (int) $book->id, 0, (int) $storeSellerId, $new,
                        $seller->seller_location_id ? (int) $seller->seller_location_id : null,
                        ['actor_type' => 'seller', 'actor_id' => $seller->id, 'note' => 'Skaner orqali qoldiq']
                    );
                    $new = (int) $book->fresh()->count;

                    return response()->json([
                        'success' => true,
                        'type' => 'book',
                        'id' => $book->id,
                        'name' => $book->name,
                        'stock' => $new,
                        'in_stock' => $new > 0,
                    ]);
                }
                if ($type === 'book') {
                    return response()->json(['success' => false, 'message' => "Bu ISBN sizning do'koningizda topilmadi."], 404);
                }
            } elseif ($type === 'book') {
                return response()->json(['success' => false, 'message' => "ISBN formati noto'g'ri."], 422);
            }
        }

        // ── Kanselyariya (shtrix-kod) ──
        if ($type === 'stationery' || $type === null) {
            $normalized = $this->normalizeBarcode($code);
            if ($normalized !== null) {
                $stationery = Seller::find($storeSellerId)->stationeries()->where('barcode', $normalized)->first();
                if ($stationery) {
                    $new = $hasStock
                        ? (int) $data['stock']
                        : max(0, (int) $stationery->stock + (int) $data['delta']);
                    $stationery->stock = $new;
                    $stationery->save();

                    return response()->json([
                        'success' => true,
                        'type' => 'stationery',
                        'id' => $stationery->id,
                        'name' => $stationery->name,
                        'stock' => $new,
                        'in_stock' => $new > 0,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => "Bu kod bo'yicha do'koningizda mahsulot topilmadi.",
        ], 404);
    }

    /**
     * DB'da turli xil yozilgan qiymatlarni Flutter forma kutadigan kodga
     * moslashtiramiz.
     *
     * MUHIM: bular `CatalogService::canon*` bilan BIR XIL bo'lishi shart.
     * Ilgari bu yerda "tanib bo'lmasa — uz / latin / soft" degan standart
     * tarmoq bor edi: karta "Русский" bo'lsa, ilovaga "uz" ketardi, keyin
     * ilova o'sha "uz" ni qaytarib yuborganda server uni karta bilan
     * solishtirib "boshqa nashr" deb rad etardi (409). Endi ikkala yo'nalish
     * ham bitta manbadan — tanib bo'lmasa `null` (ya'ni "noma'lum", hech nima
     * bilan ziddiyatga kirmaydi).
     */
    protected function normalizeLanguageOut(?string $raw): ?string
    {
        return \App\Services\Catalog\CatalogService::canonLang($raw);
    }

    protected function normalizeLangTypeOut(?string $raw): ?string
    {
        return \App\Services\Catalog\CatalogService::canonScript($raw);
    }

    protected function normalizeCoverTypeOut(?string $raw): ?string
    {
        return \App\Services\Catalog\CatalogService::canonCover($raw);
    }

    private function normalizeBarcode(?string $raw): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $raw) ?? '';
        if ($clean === '') {
            return null;
        }

        return strlen($clean) >= 8 && strlen($clean) <= 14 ? $clean : null;
    }

    /**
     * ✅ VIEW ONLY - LOG YO'Q
     */
    public function getCategories(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Categories available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $categories = BookCategories::select('id', 'name_uz', 'name_ru', 'name_ja')->limit(10)->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ], 200);
    }

    /**
     * ✅ VIEW ONLY - LOG YO'Q
     */
    public function getTagsForCategory(Request $request, $category_id)
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Tags available only for Owner, Admin, and Product Manager.'
            ], 403);
        }

        $category = BookCategories::find($category_id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        $tags = $category->tags()->select('id', 'tag_name_uz', 'tag_name_ru', 'tag_name_en')->get();
        
        return response()->json([
            'success' => true,
            'data' => $tags,
        ], 200);
    }
    /**
 * ✅ Kategoriyalarni taglari bilan birga qaytarish (barcha tillar to'liq)
 */
public function getStationeryCategories(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    if (!$this->hasProductAccess($seller)) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied. Categories available only for Owner, Admin, and Product Manager.'
        ], 403);
    }

    // Kategoriyalarni taglari bilan yuklash
    $categories = StationeryCategory::with(['tags' => function ($query) {
        $query->select('id', 'name_uz', 'name_ru', 'name_en', 'name_ja');
    }])
    ->select('id', 'name_uz', 'name_ru', 'name_en', 'name_ja', 'slug')
    ->orderBy('created_at')
    ->get();

    // Har bir kategoriyaga qo'shimcha ma'lumotlar qo'shish
    $formattedCategories = $categories->map(function ($category) {
        return [
            'id'         => $category->id,
            'name_uz'    => $category->name_uz,
            'name_ru'    => $category->name_ru,
            'name_en'    => $category->name_en,
            'name_ja'    => $category->name_ja,
            'slug'       => $category->slug,
            'tags'       => $category->tags->map(function ($tag) {
                return [
                    'id'       => $tag->id,
                    'name_uz'  => $tag->name_uz,
                    'name_ru'  => $tag->name_ru,
                    'name_en'  => $tag->name_en,
                    'name_ja'  => $tag->name_ja,
                ];
            })->values()->all(), // indekslangan massiv qaytarish uchun
        ];
    });

    return response()->json([
        'success' => true,
        'data'    => $formattedCategories,
    ], 200);
}

public function generateDescription(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
 
    if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Categories available only for Owner, Admin, and Product Manager.'
            ], 403);
        }
 
    // ── Premium tekshiruvi ────────────────────────────────────────
    $storeSeller = \App\Models\Seller::find($this->getStoreSellerId($seller));
    $isPremium = $storeSeller ? $this->premiumService->isSellerPremium($storeSeller) : false;
 
    if (!$isPremium) {
        return response()->json([
            'success'          => false,
            'message'          => "AI tavsif yozish faqat Premium do\'konlar uchun mavjud.",
            'premium_required' => true,
        ], 403);
    }
 
    $validator = Validator::make($request->all(), [
        'type'      => 'required|string|in:book,stationery',
        'name'      => 'required|string|max:255',
        // Book uchun
        'author'    => 'nullable|string|max:255',
        'pages'     => 'nullable|integer|min:1',
        'lang'      => 'nullable|string',
        'coverType' => 'nullable|string',
        'year'      => 'nullable|integer',
        // Stationery uchun
        'material'  => 'nullable|string|max:255',
        // Umumiy
        'category'  => 'nullable|string|max:255',
        'tags'      => 'nullable|array',
        'tags.*'    => 'string',
        'price'     => 'nullable|numeric|min:0',
    ]);
 
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }
 
    $type = $request->input('type');
    $isBook = $type === 'book';
 
    // ── Prompt qurish ─────────────────────────────────────────────
    $tagStr      = implode(', ', $request->input('tags', []));
    $priceFormatted = $request->filled('price')
        ? number_format((float)$request->price) . " so'm"
        : null;
 
    if ($isBook) {
        $details = collect([
            "Kitob nomi: {$request->name}",
            $request->filled('author')    ? "Muallif: {$request->author}"         : null,
            $request->filled('category')  ? "Kategoriya: {$request->category}"    : null,
            $request->filled('lang')      ? "Til: {$request->lang}"               : null,
            $request->filled('coverType') ? "Muqova: {$request->coverType}"       : null,
            $request->filled('pages')     ? "Sahifalar: {$request->pages} ta"     : null,
            $request->filled('year')      ? "Yil: {$request->year}"               : null,
            $tagStr                        ? "Teglar: {$tagStr}"                   : null,
            $priceFormatted                ? "Narx: {$priceFormatted}"             : null,
        ])->filter()->implode("\n");
 
        $prompt = <<<EOT
Sen professional kitob do'koni tavsif yozuvchisisang.
Quyidagi kitob haqida FAQAT O'ZBEK TILIDA 3-5 jumladan iborat qisqa, jozibali va aniq tavsif yoz.
 
Kitob ma'lumotlari:
{$details}
 
QOIDALAR:
- Tavsif xaridor uchun yozilgan bo'lsin (2-chi shaxsda emas, "kitob haqida" formatda)
- Kitobning asosiy mavzusi va foydasini ko'rsat
- "Bu kitob..." yoki "Kitobda..." deb boshlash mumkin
- Emoji ishlatma
- Maksimal 5 jumla
- Faqat tavsif matni yoz, boshqa hech narsa yozma
EOT;
    } else {
        $details = collect([
            "Mahsulot nomi: {$request->name}",
            $request->filled('material')  ? "Material: {$request->material}"      : null,
            $request->filled('category')  ? "Kategoriya: {$request->category}"    : null,
            $tagStr                        ? "Teglar: {$tagStr}"                   : null,
            $priceFormatted                ? "Narx: {$priceFormatted}"             : null,
        ])->filter()->implode("\n");
 
        $prompt = <<<EOT
Sen professional kanselyariya do'koni tavsif yozuvchisisang.
Quyidagi mahsulot haqida FAQAT O'ZBEK TILIDA 3-5 jumladan iborat qisqa, jozibali va aniq tavsif yoz.
 
Mahsulot ma'lumotlari:
{$details}
 
QOIDALAR:
- Tavsif xaridor uchun yozilgan bo'lsin
- Mahsulotning asosiy xususiyatlari va foydasini ko'rsat
- "Bu mahsulot..." yoki "Mahsulot..." deb boshlash mumkin
- Emoji ishlatma
- Maksimal 5 jumla
- Faqat tavsif matni yoz, boshqa hech narsa yozma
EOT;
    }
 
    // ── AI ga so'rov ──────────────────────────────────────────────
    try {
        $description = $this->ai->askSimple($prompt, 300, 0.7);
        $description = trim($description);
 
        // Agar bo'sh yoki juda qisqa kelsa
        if (mb_strlen($description) < 20) {
            return response()->json([
                'success' => false,
                'message' => 'AI tavsif yarata olmadi. Yana urinib ko\'ring.',
            ], 500);
        }
 
        $this->writeLog(
            $seller,
            'AI tavsif yaratdi',
            "{$request->name} ({$type})"
        );
 
        return response()->json([
            'success'     => true,
            'description' => $description,
        ]);
 
    } catch (\Throwable $e) {
        Log::error('generateDescription error', [
            'seller_id' => $seller->id,
            'error'     => $e->getMessage(),
        ]);
 
        return response()->json([
            'success' => false,
            'message' => 'AI xizmatida xatolik yuz berdi.',
        ], 500);
    }
}
 
// =========================================================================
//  RECOMMENDED TOGGLE
//
//  POST /api/seller/products/set-recommended
//
//  Request:
//  {
//    "product_id": 123,
//    "type": "book",          // 'book' | 'stationery'
//    "recommended": true,     // true | false
//    "expires_days": 30       // necha kun amal qilsin (null = abadiy)
//  }
//
//  Response:
//  {
//    "success": true,
//    "recommended": true,
//    "expires_at": "2026-05-10T00:00:00.000000Z"  // null = abadiy
//  }
// =========================================================================
 
/**
 * ✅ MAHSULOTNI RECOMMENDED QILISH / BEKOR QILISH
 * Faqat PREMIUM do'kon egalari foydalana oladi.
 *
 * POST /api/seller/products/set-recommended
 */
public function setRecommended(Request $request)
{
    $seller = Auth::guard('seller')->user();
    if (!$seller) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }
 
    if (!$this->hasProductAccess($seller)) {
            return response()->json([
                'success' => false, 
                'message' => 'Access denied. Categories available only for Owner, Admin, and Product Manager.'
            ], 403);
        }
 
    // ── Premium tekshiruvi ────────────────────────────────────────
    // Premium ni do'kon egasidan (storeSellerId) olamiz
    $storeSellerId = $seller->parent_id ?: $seller->id;
    $storeSeller = \App\Models\Seller::find($storeSellerId);
    $isPremium = $storeSeller ? $this->premiumService->isSellerPremium($storeSeller) : false;
 
    if (!$isPremium) {
        return response()->json([
            'success' => false,
            'message' => 'Bu xizmat faqat Premium do\'konlar uchun mavjud.',
            'premium_required' => true,
        ], 200);
    }
 
    $validator = Validator::make($request->all(), [
        'product_id'  => 'required|integer',
        'type'        => 'required|string|in:book,stationery',
        'recommended' => 'required|boolean',
        'expires_days'=> 'nullable|integer|min:1|max:30',
    ]);
 
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }
 
    $storeSellerId = $storeSellerId; // yuqorida aniqlangan
    $type          = $request->input('type');
    $isRecommended = (bool) $request->input('recommended');
    $expiresDays   = $request->input('expires_days');
 
    // ── Mahsulotni topish ─────────────────────────────────────────
    if ($type === 'book') {
        $product = Books::where('seller_id', $storeSellerId)
            ->where('id', $request->product_id)
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->first();
    } else {
        $product = Stationery::where('seller_id', $storeSellerId)
            ->where('id', $request->product_id)
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->first();
    }
 
    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Mahsulot topilmadi yoki moderatsiyadan o\'tmagan.',
        ], 404);
    }
 
    // ── Recommended limit tekshiruvi ──────────────────────────────
    // Premium do'kon bir vaqtda maksimal 10 ta mahsulotni recommended qila oladi
    if ($isRecommended) {
        $activeRecCount = $type === 'book'
            ? Books::where('seller_id', $storeSellerId)
                ->where('recommended', true)
                ->where(fn($q) => $q
                    ->whereNull('recommendedExpiresAt')
                    ->orWhere('recommendedExpiresAt', '>', now())
                )
                ->where('id', '!=', $product->id)
                ->count()
            : Stationery::where('seller_id', $storeSellerId)
                ->where('recommended', true)
                ->where(fn($q) => $q
                    ->whereNull('recommendedExpiresAt')
                    ->orWhere('recommendedExpiresAt', '>', now())
                )
                ->where('id', '!=', $product->id)
                ->count();
 
        if ($activeRecCount >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Bir vaqtda maksimal 10 ta mahsulotni recommended qilish mumkin.',
                'limit'   => 10,
                'current' => $activeRecCount,
            ], 422);
        }
    }
 
    // ── Yangilash ─────────────────────────────────────────────────
    $expiresAt = null;
    if ($isRecommended && $expiresDays) {
        $expiresAt = now()->addDays($expiresDays);
    }
 
    $product->update([
        'recommended'           => $isRecommended,
        'recommendedExpiresAt'  => $isRecommended ? $expiresAt : null,
    ]);
 
    $action = $isRecommended
        ? 'Mahsulotni recommended qildi' . ($expiresAt ? " ({$expiresDays} kun)" : ' (abadiy)')
        : 'Mahsulotdan recommended olib tashladi';
 
    $this->writeLog($seller, $action, "[{$type}] {$product->name}");
 
    return response()->json([
        'success'       => true,
        'recommended'   => $isRecommended,
        'expires_at'    => $expiresAt?->toISOString(),
        'message'       => $isRecommended
            ? "Mahsulot recommended ro'yxatiga qo'shildi."
            : "Mahsulot recommended ro'yxatidan olib tashlandi.",
    ]);
}
}
