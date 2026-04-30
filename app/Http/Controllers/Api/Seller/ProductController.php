<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\BookCategories;
use App\Models\Stationery;
use App\Models\StationeryCategory;
use App\Models\StatyioneryTag;
use App\Models\StatyioneryVariant;
use App\Models\Seller;
use App\Models\BookTag;
use App\Models\Sold;
use App\Models\Gifts;
use App\Models\SellerStaffLog;
use App\Services\SellerPremiumService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        protected \App\Services\OpenAIService $ai,
        protected SellerPremiumService $premiumService
    )
    {
        $this->middleware('auth:seller');
    }

    /**
     * ✅ OWNER DO'KON ID QAYTARADI
     */
    private function getStoreSellerId($seller)
    {
        return $seller->parent_id ?: $seller->id;
    }

    /**
     * ✅ PRODUCT ACCESS: OWNER, ADMIN (1), PRODUCT MANAGER (2)
     */
    private function hasProductAccess($seller)
    {
        // parent_id = NULL → OWNER → FULL ACCESS
        // parent_id mavjud + role=1 yoki 2 → ACCESS
        return !$seller->parent_id || in_array($seller->role, [1, 2]);
    }

    /**
     * ✅ LOG YOZISH (FAQAT AMAL UCHUN)
     */
    private function writeLog($staff, $action, $details = '')
    {
        $storeSellerId = $this->getStoreSellerId($staff);
        
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $storeSellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} ({$staff->role}) → {$action}" . ($details ? " | {$details}" : ''),
        ]);
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
        $books = Seller::find($storeSellerId)->books()
            ->where('is_hidden', false)
            ->with(['category', 'tags'])
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

    // COUNTS (filtr va searchdan mustaqil)
    $booksCounts = [
        'active' => Seller::find($storeSellerId)->books()
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->count(),
        'cancelled' => Seller::find($storeSellerId)->books()
            ->where('is_hidden', false)
            ->where('is_approved', 2)
            ->count(),
        'low_stock' => Seller::find($storeSellerId)->books()
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->where('count', '<', 4)
            ->count(),
    ];

    $stationeryCounts = [
        'active' => Seller::find($storeSellerId)->stationeries()
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->count(),
        'cancelled' => Seller::find($storeSellerId)->stationeries()
            ->where('is_hidden', false)
            ->where('is_approved', 2)
            ->count(),
        'low_stock' => Seller::find($storeSellerId)->stationeries()
            ->where('is_hidden', false)
            ->where('is_approved', 1)
            ->where('stock', '<', 4)
            ->count(),
    ];

    // Asosiy querylar
    $booksQuery = Seller::find($storeSellerId)->books()
        ->where('is_hidden', false)
        ->with(['category', 'tags']);

    $stationeryQuery = Seller::find($storeSellerId)->stationeries()
        ->where('is_hidden', false)
        ->with(['category', 'variants', 'tags']);

    // Books search
    if ($booksSearch !== '') {
        $booksQuery->where('name', 'like', "%$booksSearch%");
    }

    // Stationery search
    if ($stationerySearch !== '') {
        $stationeryQuery->where('name', 'like', "%$stationerySearch%");
    }

    // Books filter
    if ($booksFilter === 'active') {
        $booksQuery->where('is_approved', 1);
    } elseif ($booksFilter === 'cancelled') {
        $booksQuery->where('is_approved', 2);
    } elseif ($booksFilter === 'low_stock') {
        $booksQuery->where('is_approved', 1)->where('count', '<', 4);
    }

    // Stationery filter
    if ($stationeryFilter === 'active') {
        $stationeryQuery->where('is_approved', 1);
    } elseif ($stationeryFilter === 'cancelled') {
        $stationeryQuery->where('is_approved', 2);
    } elseif ($stationeryFilter === 'low_stock') {
        $stationeryQuery->where('is_approved', 1)->where('stock', '<', 4);
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
        $count = Seller::find($storeSellerId)->books()
            ->where('is_hidden', false)
            ->count();

        return response()->json([
            'success' => true,
            'products' => $count,
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
        }
    }

    // 4. Mahsulotni yaratish
    $stationery = Stationery::create([
        'seller_id'      => $storeSellerId,
        'category_id'    => $request->category_id,
        'name'           => $request->name,
        'barcode'        => $this->normalizeBarcode($request->input('barcode')),
        'material'       => $request->material,
        'price'          => $request->price,
        'discount_price' => $request->discountPrice ?? 0,
        'stock'          => $request->stock,
        'description'    => $request->description,
        'images'         => $imagePaths,
        'is_approved'    => 0,
    ]);

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
                }
            }

            // Relationship orqali saqlash
            $stationery->variants()->create([
                'color_name' => $colorName,
                'image_path' => $variantImagePath,
            ]);
        }
    }

    $this->writeLog($seller, 'Yangi kanselyariya mahsuloti qo‘shdi', $stationery->name);

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
}

// Tartiblangan existing rasmlar + yangi yuklanganlar
$finalImages = $existingImages;

if ($request->hasFile('images')) {
    foreach ($request->file('images') as $index => $image) {
        if ($image->isValid()) {
            $filename = time() . "_main_{$index}_" . uniqid() . "." . $image->getClientOriginalExtension();
            $path = $image->storeAs('stationery', $filename, 'public');
            $finalImages[] = $path;
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
        }
        $variant->delete();
    });


    // === VARIANTLARNI YANGILASH / YARATISH ===
    if ($request->has('variant_colors')) {
        foreach ($request->variant_colors as $index => $vData) {
            $variantImagePath = null;

            // Yangi variant rasmi yuklanganmi?
            if (isset($vData['image']) && $vData['image'] instanceof \Illuminate\Http\UploadedFile) {
    $vFile = $vData['image'];
    $vFilename = time() . "_var_" . uniqid() . "." . $vFile->getClientOriginalExtension();
    $variantImagePath = $vFile->storeAs('stationery/variants', $vFilename, 'public');
}


            if (isset($vData['id']) && !empty($vData['id'])) {
                // Eskisini yangilash
                $variant = $stationery->variants()->find($vData['id']);
                if ($variant) {
                    $updateData = [
                        'color_name' => $vData['color_name'],
                        'stock' => $vData['stock'], // YANGI: stock yangilanadi
                    ];

                    if ($variantImagePath) {
                        if ($variant->image_path) {
                            Storage::disk('public')->delete($variant->image_path);
                        }
                        $updateData['image_path'] = $variantImagePath;
                    }

                    $variant->update($updateData);
                }
            } else {
                // Yangi variant yaratish
                $stationery->variants()->create([
                    'color_name' => $vData['color_name'],
                    'stock' => $vData['stock'], // YANGI: stock saqlanadi
                    'image_path' => $variantImagePath,
                ]);
            }
        }
    }

    // === TAGLARNI SYNC QILISH ===
    $tagIds = $request->input('tag_ids', []);
    $stationery->tags()->sync($tagIds);

    // === ASOSIY MA'LUMOTLARNI YANGILASH ===
    $stationery->update([
        'name' => $request->name,
        'barcode' => $this->normalizeBarcode($request->input('barcode')),
        'material' => $request->material ?? $stationery->material,
        'price' => $request->price,
        'discount_price' => $request->discountPrice ?? 0,
        'stock' => $request->stock,
        'description' => $request->description,
        'images' => $finalImages,
        'category_id' => $request->category_id,
        'is_approved' => 0, // Moderatsiyaga qaytadi
    ]);

    // Log yozish
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
        $product = \App\Models\Seller::find($storeSellerId)->books()->find($productId);
    }

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    // LOG YOZISH
    $prefix = ($type === 'stationery') ? '[Kanselyariya] ' : '[Kitob] ';
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
        $product = Gifts::where('seller_id', $storeSellerId)->find($productId);
    } else {
        $product = \App\Models\Seller::find($storeSellerId)->books()->find($productId);
    }

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    $oldStatus = $product->status ? 'Faol' : 'Nofaol';
    $newStatus = !$product->status ? 'Faol' : 'Nofaol';
    $prefix = ($type === 'stationery') ? '[Kanselyariya] ' : '[Kitob] ';

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
                    $imagePaths[] = str_replace('public/', '', $path);
                }
            }
        }

        // Avto-approve: ISBN bor va bazada (boshqa sellerda ham) shu ISBN
        // YOKI nom+muallif mos kelsa — yangi qator ham tasdiqlangan deb yoziladi.
        $autoApproved = $this->shouldAutoApprove(
            $canonicalIsbn,
            $request->input('name'),
            $request->input('author')
        );

        $book = Books::create([
            'seller_id' => $storeSellerId,
            'name' => $request->input('name'),
            'author' => $request->input('author'),
            'isbn' => $canonicalIsbn,
            'pages' => $request->input('pages'),
            'lang' => $request->input('language'),
            'langType' => $request->input('languageWrite'),
            'coverType' => $request->input('coverType'),
            'price' => $request->input('price'),
            'discountPrice' => $request->input('discountPrice', 0),
            'count' => $request->input('count'),
            'description' => $request->input('description'),
            'images' => $imagePaths,
            'year' => 2025,
            'category_id' => $request->input('category_id'),
            'status' => true,
            'is_hidden' => false,
            'is_approved' => $autoApproved ? 1 : 0,
        ]);

        if ($request->has('tag_ids')) {
            $book->tags()->attach($request->input('tag_ids'));
        }

        // ✅ LOG YOZISH (FAQAT CREATE UCHUN)
        $logSuffix = $autoApproved ? ' (avto-tasdiqlandi)' : '';
        $this->writeLog($seller, 'Yangi maxsulot qo\'shdi' . $logSuffix, $book->name);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $book->load(['category', 'tags']),
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

    $validator = Validator::make($request->all(), [
        'id' => 'required|integer',
        'name' => 'required|string|max:255',
        'author' => 'required|string|max:255',
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

    $product = Seller::find($storeSellerId)->books()->find($request->id);
    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
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
        if (is_string($image) && in_array($image, $currentImages)) {
            try {
                Storage::disk('public')->delete($image);
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

    // ISBN'ni kanonik shaklga keltiramiz va avto-approve sharti tekshiriladi.
    // Update'da ham boshlang'ich ID'ni hisobga olmasdan: agar boshqa qatorlarda
    // mos keluvchi tasdiqlangan kitob bo'lsa — bu o'zgartirish ham approved.
    $canonicalIsbn = Books::normalizeIsbn($request->input('isbn'));
    $autoApproved  = $this->shouldAutoApprove(
        $canonicalIsbn,
        $request->name,
        $request->author,
        excludeBookId: $product->id,
    );

    // Mahsulotni yangilash
    $product->update([
        'name' => $request->name,
        'author' => $request->author,
        'isbn' => $canonicalIsbn,
        'pages' => $request->pages,
        'lang' => $request->language,
        'langType' => $request->languageWrite,
        'coverType' => $request->coverType,
        'price' => $request->price,
        'discountPrice' => $request->discountPrice ?? 0,
        'discountExpiresAt' => $request->filled('discountExpiresAt') ? $request->discountExpiresAt : null,
        'count' => $request->count,
        'description' => $request->description,
        'images' => $finalImages,
        'category_id' => $request->category_id,
        'is_approved' => $autoApproved ? 1 : 0,
    ]);
    $product->tags()->sync($request->input('tag_ids', []));
    $logSuffix = $autoApproved ? ' (avto-tasdiqlandi)' : '';
    $this->writeLog($seller, 'Mahsulot ma\'lumotlarini yangiladi' . $logSuffix, $product->name);
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

    $storeSellerId = $this->getStoreSellerId($seller);
    $product = null;

    // 1. Turni aniqlash va mahsulotni topish
    if ($type === 'stationery') {
        $product = \App\Models\Stationery::where('seller_id', $storeSellerId)
            ->where('id', $id)
            ->first();
    }elseif ($type === 'gift') {
        $product = \App\Models\Gifts::where('seller_id', $storeSellerId)
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

    $result = [
        'id'             => $product->id,
        'name'           => $product->name,
        'type'           => $type,
        'category_id'    => $product->category_id,
        
        // Umumiy statistikalar
        'total_sales'    => $product->totalSales ?? 0,
        'total_revenue'  => $product->totalRevenue ?? 0,
        'total_clients'  => $product->totalClients ?? 0,
        
        // Haftalik statistikalar
        'total_sales_week'   => $product->totalSalesWeek ?? 0,
        'total_clients_week' => $product->totalClientsWeek ?? 0,
        'total_revenue_week' => $product->totalRevenueWeek ?? 0,
        
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
     * Avto-approve qarori — yangi yoki yangilangan kitobni admin
     * tasdiqlashisiz darrov nashrga chiqarish mumkinmi?
     *
     * Sharti:
     *  1) ISBN bor va bazada (boshqa sellerda ham) is_approved=1, is_hidden=0
     *     qator ichida shu ISBN MOS — true.
     *  2) Aks holda nomi va muallifi case-insensitive trim bilan aynan mos
     *     keladigan tasdiqlangan kitob bor — true.
     *  3) Hech biri bajarilmasa — false (admin tasdiqlashi kutiladi).
     */
    private function shouldAutoApprove(
        ?string $canonicalIsbn,
        string $name,
        string $author,
        ?int $excludeBookId = null
    ): bool {
        $query = Books::query()
            ->where('is_approved', 1)
            ->where('is_hidden', 0);

        if ($excludeBookId) {
            $query->where('id', '!=', $excludeBookId);
        }

        // 1-shart: ISBN MOS
        if ($canonicalIsbn !== null) {
            $isbnMatch = (clone $query)->whereIsbn($canonicalIsbn)->exists();
            if ($isbnMatch) return true;
        }

        // 2-shart: nom + muallif case-insensitive trim mos
        $normalizedName   = mb_strtolower(trim($name));
        $normalizedAuthor = mb_strtolower(trim($author));
        if ($normalizedName === '' || $normalizedAuthor === '') return false;

        return (clone $query)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->whereRaw('LOWER(TRIM(author)) = ?', [$normalizedAuthor])
            ->exists();
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

        $canonical = Books::normalizeIsbn($isbn);
        if ($canonical === null) {
            return response()->json([
                'success' => false,
                'message' => "ISBN formati noto'g'ri (10 yoki 13 raqam bo'lishi kerak).",
            ], 422);
        }

        $books = Books::query()
            ->whereIsbn($canonical)
            ->where('is_approved', 1)
            ->where('is_hidden', 0)
            ->with('tags:id')
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
                        'pages'         => (int) ($book->pages ?? 0),
                        'language'      => $this->normalizeLanguageOut($book->lang),
                        'languageWrite' => $this->normalizeLangTypeOut($book->langType),
                        'coverType'     => $this->normalizeCoverTypeOut($book->coverType),
                        'category_id'   => $book->category_id,
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
                'pages'         => (int) ($book->pages ?? 0),
                'language'      => $this->normalizeLanguageOut($book->lang),
                'languageWrite' => $this->normalizeLangTypeOut($book->langType),
                'coverType'     => $this->normalizeCoverTypeOut($book->coverType),
                'category_id'   => $book->category_id,
                'tag_ids'       => $book->tags->pluck('id')->values()->all(),
                'year'          => (int) ($book->year ?? 0),
            ],
        ]);
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
     * DB'da turli xil yozilgan til qiymatlarini Flutter forma kutadigan
     * uch belgili kodga moslashtiramiz.
     */
    private function normalizeLanguageOut(?string $raw): ?string
    {
        $v = mb_strtolower(trim((string) $raw));
        if ($v === '') return null;
        return match (true) {
            str_contains($v, 'rus') || $v === 'ru'                         => 'ru',
            str_contains($v, 'en') || str_contains($v, 'ingl')             => 'en',
            str_contains($v, 'qq') || str_contains($v, 'qora')             => 'qq',
            default                                                         => 'uz',
        };
    }

    /**
     * "Lotin"/"Kirill" varianti — DB'da turli yozilgan bo'lishi mumkin.
     */
    private function normalizeLangTypeOut(?string $raw): ?string
    {
        $v = mb_strtolower(trim((string) $raw));
        if ($v === '') return null;
        return str_contains($v, 'kir') || str_contains($v, 'cyr')
            ? 'cyrillic'
            : 'latin';
    }

    /**
     * "Yumshoq"/"Qattiq" → "soft"/"hard".
     */
    private function normalizeCoverTypeOut(?string $raw): ?string
    {
        $v = mb_strtolower(trim((string) $raw));
        if ($v === '') return null;
        return str_contains($v, 'qat') || str_contains($v, 'hard')
            ? 'hard'
            : 'soft';
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
