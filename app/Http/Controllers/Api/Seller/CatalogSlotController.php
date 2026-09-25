<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\CatalogSlotPurchase;
use App\Models\Seller;
use App\Models\SellerStaffLog;
use App\Services\Catalog\CatalogSlotService;
use App\Support\ProductImageUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * KATALOG JOYI — do'kon tomoni.
 *
 * Do'kon o'z kitobiga "birinchi o'rin" sotib oladi: kitob sahifasida mijozga
 * shu do'kon tanlangan holda ochiladi. Bitta kartada bitta joy.
 */
class CatalogSlotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    private function storeSellerId(): int
    {
        $seller = Auth::guard('seller')->user();

        return (int) ($seller->parent_id ?: $seller->id);
    }

    /** Reklama bo'limlaridagi kabi: faqat egasi va yuqori rollar. */
    private function denyWithoutAccess(): ?JsonResponse
    {
        $seller = Auth::guard('seller')->user();
        if (! $seller) {
            return response()->json(['success' => false, 'message' => 'Avtorizatsiya talab qilinadi'], 401);
        }
        if ($seller->parent_id && (int) $seller->role > 1) {
            return response()->json(['success' => false, 'message' => "Bu bo'limga ruxsat yo'q."], 403);
        }

        return null;
    }

    /**
     * GET catalog-slots/info — narx, muddat chegaralari, balans, mening joylarim.
     */
    public function info(CatalogSlotService $slots): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $settings = $slots->settings();
        $sellerId = $this->storeSellerId();
        $seller = Seller::query()->find($sellerId);

        return response()->json([
            'success' => true,
            'data' => [
                'is_active' => (bool) $settings->is_active && (int) $settings->price_per_month > 0,
                'price_per_month' => (int) $settings->price_per_month,
                'price_per_day' => $slots->priceFor(1, $settings),
                'min_days' => (int) $settings->min_days,
                'max_days' => (int) $settings->max_days,
                'balance' => (int) ($seller->balance ?? 0),
                'purchases' => $this->purchaseList($sellerId),
            ],
        ]);
    }

    /**
     * GET catalog-slots/books — joy sotib olish mumkin bo'lgan kitoblar.
     *
     * Faqat global kartaga ulangan va sotuvdagi kitoblar. Har biri uchun joy
     * bo'shligi va kartada nechta raqobatchi borligi ko'rsatiladi — do'kon
     * qaysi kitobga pul tikishni shundan biladi.
     */
    public function books(Request $request, CatalogSlotService $slots): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $sellerId = $this->storeSellerId();
        $search = trim((string) $request->query('q', ''));

        $books = Books::query()
            ->where('seller_id', $sellerId)
            ->whereNotNull('edition_id')
            ->where('is_hidden', false)
            ->whereNull('archived_at')
            ->where('is_approved', 1)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->with('edition:id,title,offers_count,in_stock_offers_count')
            ->latest('updated_at')
            ->limit(100)
            ->get(['id', 'name', 'images', 'edition_id', 'price', 'discountPrice']);

        // Bandlikni bitta so'rovda olamiz (har kitob uchun alohida so'rov emas)
        $editionIds = $books->pluck('edition_id')->filter()->unique()->all();
        $busy = empty($editionIds) ? collect() : \App\Models\CatalogSlotPurchase::query()
            ->blocking()
            ->whereIn('edition_id', $editionIds)
            ->get(['edition_id', 'seller_id', 'ends_at'])
            ->keyBy('edition_id');

        return response()->json([
            'success' => true,
            'data' => $books->map(function (Books $book) use ($busy, $sellerId) {
                $slot = $busy->get($book->edition_id);

                return [
                    'id' => (int) $book->id,
                    'name' => $book->name,
                    'image' => ProductImageUrls::originalUrl($book->first_image),
                    'edition_id' => (int) $book->edition_id,
                    'offers_count' => (int) ($book->edition->offers_count ?? 0),
                    'available' => $slot === null,
                    'occupied_by_me' => $slot !== null && (int) $slot->seller_id === $sellerId,
                    'busy_until' => $slot?->ends_at?->toDateString(),
                ];
            })->values(),
        ]);
    }

    /**
     * GET catalog-slots/quote?book_id=&days= — narx va bandlikni oldindan ko'rsatish.
     */
    public function quote(Request $request, CatalogSlotService $slots): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer',
            'days' => 'required|integer|min:1|max:365',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => "So'rov noto'g'ri", 'errors' => $validator->errors()], 422);
        }

        $book = Books::query()
            ->where('seller_id', $this->storeSellerId())
            ->whereKey((int) $request->input('book_id'))
            ->first();

        if (! $book) {
            return response()->json(['success' => false, 'message' => 'Kitob topilmadi'], 404);
        }

        $settings = $slots->settings();
        $days = (int) $request->input('days');
        $occupied = $book->edition_id ? $slots->occupiedFor((int) $book->edition_id) : null;
        $mine = $occupied && (int) $occupied->seller_id === $this->storeSellerId();

        return response()->json([
            'success' => true,
            'data' => [
                'book_id' => (int) $book->id,
                'edition_id' => $book->edition_id ? (int) $book->edition_id : null,
                'linked' => (bool) $book->edition_id,
                'days' => $days,
                'price' => $slots->priceFor($days, $settings),
                'min_days' => (int) $settings->min_days,
                'max_days' => (int) $settings->max_days,
                // Kartada nechta do'kon bor — joyning qimmati shundan bilinadi
                'offers_count' => $book->edition_id ? (int) ($book->edition?->offers_count ?? 0) : 0,
                'available' => $book->edition_id && ! $occupied,
                'occupied_by_me' => (bool) $mine,
                'busy_until' => $occupied?->ends_at?->toDateString(),
            ],
        ]);
    }

    /**
     * POST catalog-slots — sotib olish. Balans darhol yechiladi, joy moderatsiyaga tushadi.
     */
    public function store(Request $request, CatalogSlotService $slots): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'book_id' => 'required|integer',
            'days' => 'required|integer|min:1|max:365',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => "So'rov noto'g'ri", 'errors' => $validator->errors()], 422);
        }

        $sellerId = $this->storeSellerId();
        $seller = Seller::query()->find($sellerId);
        $book = Books::query()->where('seller_id', $sellerId)->whereKey((int) $request->input('book_id'))->first();

        if (! $seller || ! $book) {
            return response()->json(['success' => false, 'message' => 'Kitob topilmadi'], 404);
        }

        $result = $slots->purchase($seller, $book, (int) $request->input('days'));

        if (! $result['ok']) {
            $status = match ($result['code']) {
                'slot_taken' => 409,
                'insufficient_balance' => 402,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'code' => $result['code'],
                'message' => $result['message'],
            ], $status);
        }

        $staff = Auth::guard('seller')->user();
        SellerStaffLog::create([
            'seller_staff_id' => $staff->id,
            'seller_id' => $sellerId,
            'text' => "Hodim: {$staff->firstname} {$staff->lastname} → Katalog joyi sotib oldi | {$book->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => "Joy band qilindi. Admin tasdiqlagach ishga tushadi.",
            'data' => $this->purchaseRow($result['purchase']->load('book:id,name,images')),
        ], 201);
    }

    /** DELETE catalog-slots/{id} — tasdiq kutayotgan joyni bekor qilish (pul qaytadi). */
    public function destroy(int $id, CatalogSlotService $slots): JsonResponse
    {
        if ($denied = $this->denyWithoutAccess()) {
            return $denied;
        }

        $purchase = CatalogSlotPurchase::query()
            ->where('seller_id', $this->storeSellerId())
            ->whereKey($id)
            ->first();

        if (! $purchase) {
            return response()->json(['success' => false, 'message' => 'Joy topilmadi'], 404);
        }

        $result = $slots->cancel($purchase);
        if (! $result['ok']) {
            return response()->json(['success' => false, 'code' => $result['code'], 'message' => $result['message']], 422);
        }

        return response()->json(['success' => true, 'message' => 'Bekor qilindi, pul balansga qaytarildi']);
    }

    /** @return array<int, array<string, mixed>> */
    private function purchaseList(int $sellerId): array
    {
        return CatalogSlotPurchase::query()
            ->where('seller_id', $sellerId)
            ->with('book:id,name,images')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (CatalogSlotPurchase $p) => $this->purchaseRow($p))
            ->all();
    }

    /** @return array<string, mixed> */
    private function purchaseRow(CatalogSlotPurchase $purchase): array
    {
        return [
            'id' => (int) $purchase->id,
            'book_id' => (int) $purchase->book_id,
            'book_name' => $purchase->book?->name,
            'image' => ProductImageUrls::originalUrl($purchase->book?->first_image),
            'status' => $purchase->status,
            'status_label' => match ($purchase->status) {
                CatalogSlotPurchase::STATUS_PENDING => 'Tasdiq kutilmoqda',
                CatalogSlotPurchase::STATUS_ACTIVE => 'Faol',
                CatalogSlotPurchase::STATUS_EXPIRED => 'Muddati tugagan',
                CatalogSlotPurchase::STATUS_REJECTED => 'Rad etilgan',
                CatalogSlotPurchase::STATUS_CANCELLED => 'Bekor qilingan',
                default => $purchase->status,
            },
            'days' => (int) $purchase->days,
            'price' => (int) $purchase->price_uzs,
            'reject_reason' => $purchase->reject_reason,
            'starts_at' => $purchase->starts_at?->toDateString(),
            'ends_at' => $purchase->ends_at?->toDateString(),
            'created_at' => $purchase->created_at?->toDateTimeString(),
            'can_cancel' => $purchase->status === CatalogSlotPurchase::STATUS_PENDING,
        ];
    }
}
