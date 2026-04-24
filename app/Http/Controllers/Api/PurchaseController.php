<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\User;
use App\Models\MyCart;
use App\Models\Sold;
use App\Models\Gifts;
use App\Models\DeliveryService;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\PromocodeHistory;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Services\OrderService;
use App\Services\CashbackHistoryService;
use App\Models\GiftCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CashbackHistoryService $cashbackHistoryService,
    ) {}

    // ── Xatolik response ──────────────────────────────────────
    private function err(string $msg, int $code = 400)
    {
        return response()->json(['status' => 'error', 'message' => $msg], $code);
    }

    // ── Mahsulot chegirmali narxi ──────────────────────────────
    private function effectivePrice($product): float
    {
        if ($product instanceof Books) {
            return (is_numeric($product->discountPrice) && $product->discountPrice > 0)
                ? (float)$product->discountPrice
                : (float)$product->price;
        }
        if ($product instanceof Stationery) {
            return (is_numeric($product->discount_price) && $product->discount_price > 0)
                ? (float)$product->discount_price
                : (float)$product->price;
        }
        return 0;
    }

    // ── Mavjud zaxira ──────────────────────────────────────────
    private function availableStock($cartItem): int
    {
        $variant = $cartItem->variant;
        $product = $cartItem->product;
        if ($variant)                       return (int)($variant->stock ?? 0);
        if ($product instanceof Books)      return (int)($product->count ?? 0);
        if ($product instanceof Stationery) return (int)($product->stock ?? 0);
        return 0;
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRUVI
    // =========================================================================

    private function validatePromocode(string $rawCode, int $userId, float $total): array
    {
        $code  = strtoupper(trim($rawCode));
        $promo = DB::table('promocodes')->where('code', $code)->first();

        if (!$promo)                                    return ['error' => 'Promokod topilmadi!'];
        if ($promo->status != 1)                        return ['error' => 'Promokod faol emas!'];
        if ($promo->usedCount >= $promo->usesLimit)     return ['error' => 'Promokod limiti tugagan!'];
        if ($promo->expires_at && now()->gt($promo->expires_at))
                                                        return ['error' => 'Promokod muddati tugagan!'];
        if (!is_null($promo->user_id) && (int)$promo->user_id !== $userId)
                                                        return ['error' => 'Bu promokod sizga tegishli emas!'];

        $used = PromocodeHistory::where('user_id', $userId)
            ->where('promocode_id', $promo->id)->exists();
        if ($used) return ['error' => 'Siz bu promokoddan allaqachon foydalangansiz!'];

        if ($promo->min_order_amount && $total < $promo->min_order_amount)
            return ['error' => "Promokod {$promo->min_order_amount} so'mdan yuqori buyurtmalarga amal qiladi."];

        $discount = match ($promo->type) {
            'uzs'     => (int) min($promo->amount, $total),
            'percent' => (int) round(($promo->amount / 100) * $total),
            default   => 0,
        };

        return ['discount' => $discount, 'promo' => $promo];
    }

    // =========================================================================
    //  CHECKOUT INFO
    // =========================================================================

    public function getCartCheckoutInfo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) return $this->err("Asosiy manzilni belgilang!", 400);

        $selectedCartIds = $request->input('selected_cart_ids', []);
        $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
        if (!empty($selectedCartIds) && is_array($selectedCartIds)) {
            $cartQuery->whereIn('id', $selectedCartIds);
        }

        $cartItems = $cartQuery->get();
        if ($cartItems->isEmpty()) return $this->err("Savatcha bo'sh!", 400);

        $totalSum = 0;
        $sellers  = [];

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product || !$product->seller_id) continue;
            $totalSum += $this->effectivePrice($product) * $item->count_item;
            $sellers[$product->seller_id] = true;
        }

        $sellerCount = count($sellers);

        $isTashkent = str_contains(mb_strtolower($location->fullAddress), 'toshkent')
                   || str_contains(mb_strtolower($location->fullAddress), 'ташкент');

        $services = DeliveryService::active()
            ->forCountry('uzbekistan')
            ->forRegion($isTashkent)
            ->get()
            ->map(function ($svc) use ($totalSum, $sellerCount) {
                $base  = $svc->priceKg;
                $price = $base + ($base * 0.5 * max(0, $sellerCount - 1));
                $final = $totalSum >= $svc->freePriceFrom ? 0 : $price;
                return [
                    'id'               => $svc->id,
                    'name'             => $svc->name,
                    'type'             => $svc->type,
                    'muddat'           => $svc->muddat,
                    'priceKg'          => $svc->priceKg,
                    'freePriceFrom'    => $svc->freePriceFrom,
                    'is_free'          => $final == 0,
                    'calculated_price' => $final,
                    'capital'          => $svc->capital,
                ];
            });

        return response()->json(['status' => 'success', 'data' => [
            'location' => [
                'id'          => $location->id,
                'lat'         => $location->lat,
                'lon'         => $location->lon,
                'fullAddress' => $location->fullAddress,
            ],
            'cart_info' => [
                'items_count'        => $cartItems->sum('count_item'),
                'seller_count'       => $sellerCount,
                'price_before_promo' => $totalSum,
                'cashback_balance'   => (int)($user->cashback ?? 0),
            ],
            'active_certificates' => GiftCertificate::where('recipient_user_id', $user->id)
                ->where('status', GiftCertificate::STATUS_ACTIVE)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get(['id', 'code', 'nominal_uzs', 'expires_at'])
                ->map(fn($cert) => [
                    'id'          => $cert->id,
                    'code'        => $cert->code,
                    'nominal_uzs' => $cert->nominal_uzs,
                    'expires_at'  => $cert->expires_at?->format('d.m.Y'),
                ]),
            'delivery_services' => $services,
        ]]);
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRISH ENDPOINT
    // =========================================================================

    public function checkPromo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $code = trim($request->query('code', ''));
        if (!$code) return $this->err('Promokod kiritilmadi!', 400);

        $selectedCartIds = $request->input('selected_cart_ids', []);
        $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
        if (!empty($selectedCartIds) && is_array($selectedCartIds)) {
            $cartQuery->whereIn('id', $selectedCartIds);
        }

        $cartItems = $cartQuery->get();
        if ($cartItems->isEmpty()) return $this->err("Savatcha bo'sh!", 400);

        $total = $cartItems->sum(fn($i) => $this->effectivePrice($i->product) * $i->count_item);
        $res   = $this->validatePromocode($code, $user->id, $total);

        if (isset($res['error'])) return $this->err($res['error']);

        return response()->json([
            'status'      => 'success',
            'message'     => 'Promokod yaroqli',
            'discount'    => $res['discount'],
            'final_price' => max(0, $total - $res['discount']),
        ]);
    }

    // =========================================================================
    //  BUYURTMA YARATISH
    // =========================================================================

    public function buy_book(Request $request)
    {
        $request->validate([
            'paymentStatus'      => 'required|boolean',
            'buyerWish'          => 'nullable|string|max:300',
            'promocode'          => 'nullable|string|max:50',
            'deliveryservice_id' => 'required|integer|exists:delivery_services,id',
            'withCashback'       => 'nullable|boolean',
            'gift_certificate_id'=> 'nullable|integer|exists:gift_certificates,id',
            'gift_id'            => 'nullable|integer|exists:gifts,id',
            'selected_cart_ids'  => 'nullable|array',
            'selected_cart_ids.*'=> 'integer',
            'is_gift_to_other'   => 'nullable|boolean',
            'with_packaging'     => 'nullable|boolean',
            'recipient_phone'    => 'nullable|string|max:20',
            'recipient_name'     => 'nullable|string|max:150',
            'recipient_region'   => 'nullable|string|max:150',
            'recipient_address'  => 'nullable|string|max:500',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        DB::beginTransaction();
        try {
            // ── Cart itemlarni olish ──────────────────────────────
            $selectedCartIds = $request->input('selected_cart_ids', []);
            $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
            if (!empty($selectedCartIds)) {
                $cartQuery->whereIn('id', $selectedCartIds);
            }
            $cartItems = $cartQuery->get();

            $location        = DB::table('locations')->where('id', $user->mainAddressID)->first();
            $deliveryService = DeliveryService::findOrFail($request->deliveryservice_id);

            if ($cartItems->isEmpty() || !$location) {
                return $this->err("Ma'lumotlar yetarli emas!", 400);
            }

            // ── Mahsulotlarni tayyorlash ──────────────────────────
            $groupedBySeller  = [];
            $allItems         = [];
            $uniqueSellerIds  = [];
            $totalSum         = 0;
            $productsToUpdate = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                if (!$product || !$product->seller_id) continue;
                if (!Seller::where('id', $product->seller_id)->where('status', 'approved')->exists()) continue;

                $qty   = $cartItem->count_item;
                $price = $this->effectivePrice($product);
                $stock = $this->availableStock($cartItem);

                if ($qty > $stock) {
                    DB::rollBack();
                    return $this->err("{$product->name} uchun yetarli zaxira yo'q! Mavjud: {$stock}", 400);
                }

                $sellerId                     = $product->seller_id;
                $groupedBySeller[$sellerId][] = $cartItem;
                $uniqueSellerIds[$sellerId]   = true;
                $totalSum                    += $price * $qty;

                $image = $variant?->image_path ?? ($product->images[0] ?? null);
                $item  = [
                    'name'       => $product->name,
                    'item_price' => $price,
                    'item_id'    => $product->id,
                    'count_item' => $qty,
                    'seller_id'  => $sellerId,
                    'type'       => $cartItem->product_type,
                    'cover'      => $image,
                ];

                if ($cartItem->product_type === 'book') {
                    $item['author'] = $product->author;
                }
                if ($cartItem->product_type === 'stationery') {
                    $item['material'] = $product->material;
                    if ($variant) {
                        $item['variant_id'] = $variant->id;
                        $item['color_name'] = $variant->color_name;
                    }
                }

                $allItems[]         = $item;
                $productsToUpdate[] = [
                    'product'  => $product,
                    'variant'  => $variant,
                    'quantity' => $qty,
                    'revenue'  => $price * $qty,
                    'user_id'  => $user->id,
                    'type'     => $cartItem->product_type,
                ];
            }

            if (empty($allItems)) return $this->err('Savatchada mahsulot topilmadi!', 400);

            $priceBeforePromo = $totalSum;

            // ── Promokod ──────────────────────────────────────────
            $discountAmount = 0;
            $appliedPromo   = null;
            $appliedPromoId = null;

            if ($request->filled('promocode')) {
                $res = $this->validatePromocode($request->promocode, $user->id, $priceBeforePromo);
                if (isset($res['error'])) {
                    DB::rollBack();
                    return $this->err($res['error']);
                }
                $discountAmount = $res['discount'];
                $appliedPromo   = $res['promo']->code;
                $appliedPromoId = $res['promo']->id;
                $totalSum       = max(0, $priceBeforePromo - $discountAmount);
            }

            // ── Yetkazish narxi ───────────────────────────────────
            $sellerCount   = count($uniqueSellerIds);
            $basePrice     = $deliveryService->priceKg;
            $calcDelivery  = $basePrice + ($basePrice * 0.5 * max(0, $sellerCount - 1));
            $deliveryPrice = $priceBeforePromo >= $deliveryService->freePriceFrom ? 0 : (int)$calcDelivery;

            // ── Cashback ──────────────────────────────────────────
            $amountBeforeCashback = $totalSum + $deliveryPrice;
            $useCashback          = $request->boolean('withCashback');
            $cashbackBalance      = (float)($user->cashback ?? 0);
            $cashbackUsed         = 0;
            $finalPrice           = $amountBeforeCashback;

            if ($useCashback && $cashbackBalance > 0 && $request->paymentStatus == 1) {
                $productPart  = max(0, $amountBeforeCashback - $deliveryPrice);
                $cashbackUsed = (int) min((int)$cashbackBalance, $productPart);

                if ($cashbackUsed > 0) {
                    $affected = DB::table('users')
                        ->where('id', $user->id)
                        ->where('cashback', '>=', $cashbackUsed)
                        ->decrement('cashback', $cashbackUsed);

                    if ($affected === 0) {
                        $cashbackUsed = 0;
                    } else {
                        $finalPrice = max(0, $amountBeforeCashback - $cashbackUsed);
                    }
                }
            }

            // ── Gift Sertifikat ───────────────────────────────────
            $certDiscount  = 0;
            $appliedCertId = null;
            $appliedCert   = null;

            if ($request->filled('gift_certificate_id') && $request->paymentStatus == 1) {
                $cert = GiftCertificate::where('id', $request->gift_certificate_id)
                    ->where('status', GiftCertificate::STATUS_ACTIVE)
                    ->where('recipient_user_id', $user->id)
                    ->first();

                if (!$cert) {
                    DB::rollBack();
                    return $this->err('Gift sertifikat topilmadi yoki sizga tegishli emas!', 400);
                }

                if ($cert->is_expired) {
                    DB::rollBack();
                    return $this->err('Gift sertifikat muddati tugagan!', 400);
                }

                $productRemainder = max(0, $finalPrice - $deliveryPrice);
                $certDiscount     = (int)min($cert->nominal_uzs, $productRemainder);
                $finalPrice       = max(0, $finalPrice - $certDiscount);
                $appliedCertId    = $cert->id;
                $appliedCert      = $cert;
            }

            // ── Gift ──────────────────────────────────────────────
            $giftId = $request->input('gift_id');
            $gift   = null;

            if ($giftId !== null) {
                $gift = Gifts::find((int)$giftId);
                if (!$gift) {
                    $gift   = Gifts::find(1);
                    $giftId = 1;
                }
            }

            if ($gift) {
                if (($gift->stock ?? 0) < 1) {
                    DB::rollBack();
                    return $this->err("{$gift->name} sovg'asi tugagan!", 400);
                }

                if ($gift->seller_id != 1) {
                    $sellerSumInCart = collect($groupedBySeller[$gift->seller_id] ?? [])
                        ->sum(fn($item) => $this->effectivePrice($item->product) * $item->count_item);

                    if (
                        !isset($groupedBySeller[$gift->seller_id]) ||
                        $sellerSumInCart < $gift->priceFrom ||
                        $sellerSumInCart > $gift->priceTo
                    ) {
                        $gift   = Gifts::find(1);
                        $giftId = 1;
                        if (!$gift || ($gift->stock ?? 0) < 1) {
                            $gift   = null;
                            $giftId = null;
                        }
                    }
                } else {
                    if ($priceBeforePromo < $gift->priceFrom || $priceBeforePromo > $gift->priceTo) {
                        $gift   = null;
                        $giftId = null;
                    }
                }
            }

            if ($gift) {
                $allItems[] = [
                    'name'       => $gift->name,
                    'item_price' => 0,
                    'item_id'    => $gift->id,
                    'count_item' => 1,
                    'seller_id'  => $gift->seller_id,
                    'type'       => 'gift',
                    'cover'      => $gift->images[0] ?? null,
                ];

                $productsToUpdate[] = [
                    'product'  => $gift,
                    'variant'  => null,
                    'quantity' => 1,
                    'revenue'  => 0,
                    'user_id'  => $user->id,
                    'type'     => 'gift',
                ];

                $gift->decrement('stock', 1);
                if ($gift->stock < 0) { $gift->stock = 0; $gift->save(); }
            }

            // ── Packaging narxi ───────────────────────────────────
            // Cashback va sertifikatdan keyin qo'shiladi —
            // chunki packaging chegirmaga kirmaydi
            $withPackaging  = $request->boolean('with_packaging');
            $bookItemCount  = collect($allItems)->where('type', 'book')->sum('count_item');
            $packagingPrice = 0;

            if ($withPackaging) {
                $packagingPrice = $bookItemCount >= 4 ? 40000 : 25000;
                $finalPrice    += $packagingPrice;
            }

            // ── Manzil ────────────────────────────────────────────
            $locationData = [
                'fullName'    => trim("{$user->name} {$user->lastname}"),
                'fullAddress' => $location->fullAddress,
                'lat'         => $location->lat,
                'lon'         => $location->lon,
                'phoneNumber' => $user->phone_number,
            ];

            // ── Asosiy buyurtma yaratish ──────────────────────────
            $purchase = Sold::create([
                'user_id'             => $user->id,
                'qr'                  => Str::random(40),
                'items'               => $allItems,
                'address'             => [$locationData],
                'deliveryType'        => $deliveryService->name,
                'deliveryPrice'       => $deliveryPrice,
                'paymentStatus'       => $request->paymentStatus ? 1 : 0,
                'amount'              => $finalPrice,
                'gift'                => $giftId ?? null,
                'buyerWish'           => Str::limit(trim(strip_tags($request->input('buyerWish', ''))), 300),
                'promocode'           => $appliedPromo,
                'discountAmount'      => $discountAmount,
                'withCashback'        => $useCashback && $cashbackUsed > 0,
                'cashbackAmount'      => $cashbackUsed,
                'gift_certificate_id' => $appliedCertId,
                'giftCertAmount'      => $certDiscount,
                'is_gift_to_other'    => $request->boolean('is_gift_to_other'),
                'with_packaging'      => $withPackaging,
                'packaging_price'     => $packagingPrice,
                'recipient_phone'     => $request->input('recipient_phone'),
                'recipient_name'      => $request->input('recipient_name'),
                'recipient_region'    => $request->input('recipient_region'),
                'recipient_address'   => $request->input('recipient_address'),
            ]);

            if ($cashbackUsed > 0) {
                $balanceAfter = (int) DB::table('users')->where('id', $user->id)->value('cashback');
                $this->cashbackHistoryService->record(
                    userId: $user->id,
                    action: 'spent',
                    amount: -$cashbackUsed,
                    order: $purchase,
                    balanceBefore: $balanceAfter + $cashbackUsed,
                    balanceAfter: $balanceAfter,
                );
            }

            // ── Seller orderlar ───────────────────────────────────
            foreach ($groupedBySeller as $sellerId => $items) {
                $sellerAmount = 0;
                $sellerOrder  = SellerOrder::create([
                    'seller_id'     => $sellerId,
                    'order_id'      => $purchase->id,
                    'client_id'     => $user->id,
                    'status'        => $request->paymentStatus == 1 ? 0 : 1,
                    'delivery_type' => $deliveryService->name,
                    'address'       => [$locationData],
                ]);

                foreach ($items as $item) {
                    $product       = $item->product;
                    $qty           = $item->count_item;
                    $price         = $this->effectivePrice($product);
                    $sellerAmount += $price * $qty;

                    SellerOrderItem::create([
                        'seller_id'  => $sellerId,
                        'order_id'   => $sellerOrder->id,
                        'product_id' => $product->id,
                        'type'       => $item->product_type,
                        'quantity'   => $qty,
                        'price'      => $price,
                        'variant_id' => $item->variant_id ?? null,
                    ]);
                }

                if (isset($gift) && $gift && $gift->seller_id != 1 && $gift->seller_id == $sellerId) {
                    SellerOrderItem::create([
                        'seller_id'  => $sellerId,
                        'order_id'   => $sellerOrder->id,
                        'product_id' => $gift->id,
                        'type'       => 'gift',
                        'quantity'   => 1,
                        'price'      => 0,
                        'variant_id' => null,
                    ]);
                }

                $sellerOrder->update(['amount' => $sellerAmount]);
            }

            // ── Courier order ─────────────────────────────────────
            $courierOrder = CourierOrder::create([
                'courier_id'   => null,
                'order_id'     => $purchase->id,
                'user_id'      => $user->id,
                'amount'       => $totalSum,
                'status'       => $request->paymentStatus == 1 ? 'pay_process' : 'pending',
                'courierPrice' => $deliveryPrice,
            ]);

            foreach ($allItems as $itm) {
                $itm_type = $itm['type'] ?? '';
                $itm_sid  = $itm['seller_id'] ?? 1;

                if ($itm_type === 'gift' && $itm_sid == 1) continue;
                if ($itm_type === 'gift' && !isset($groupedBySeller[$itm_sid])) continue;

                $sellerLocation = DB::table('seller_locations')
                    ->where('seller_id', $itm_sid)
                    ->where('is_main', true)
                    ->first();

                if (!$sellerLocation) {
                    Log::warning("Seller location not found for seller_id: {$itm_sid}");
                    continue;
                }

                CourierOrderItem::create([
                    'seller_id'          => $itm_sid,
                    'seller_location_id' => $sellerLocation->id,
                    'order_id'           => $courierOrder->id,
                    'type'               => $itm_type,
                    'product_id'         => $itm['item_id'],
                    'quantity'           => $itm['count_item'],
                    'price'              => $itm['item_price'],
                    'variant_id'         => $itm['variant_id'] ?? null,
                ]);
            }

            // ── Statistika va stock yangilash ─────────────────────
            foreach ($productsToUpdate as $data) {
                if ($data['type'] === 'gift') {
                    $data['product']->increment('totalSales', 1);
                    $data['product']->increment('totalSalesWeek', 1);
                    $data['product']->save();
                    continue;
                }
                $this->orderService->decrementStock($data);
                $this->orderService->incrementProductStats($data, $purchase->id);
            }

            // ── Promokod tarixi ───────────────────────────────────
            if ($appliedPromoId) {
                PromocodeHistory::create(['user_id' => $user->id, 'promocode_id' => $appliedPromoId]);
                DB::table('promocodes')->where('id', $appliedPromoId)->increment('usedCount');
            }

            // ── Gift Sertifikat ishlatish ──────────────────────────
            if ($appliedCert && $certDiscount > 0) {
                $appliedCert->useInPurchase((int)$certDiscount);
            }

            // ── Naqd to'lov — seller/courier orderlarni activate qilish ─
            // handleOrderPaid chaqirilmaydi: paymentStatus=0 qolsin,
            // mijoz hali to'lamagan (naqd yetkazilganda to'laydi).
            // Bekor qilish imkoni saqlanib qoladi.
            if ($request->paymentStatus == 0) {
                SellerOrder::where('order_id', $purchase->id)->update(['status' => 1]);
                CourierOrder::where('order_id', $purchase->id)->update(['status' => 'pending']);
            }

            // ── Faqat tanlangan cart itemlarni o'chirish ──────────
            if (!empty($selectedCartIds)) {
                MyCart::where('user_id', $user->id)->whereIn('id', $selectedCartIds)->delete();
            } else {
                MyCart::where('user_id', $user->id)->delete();
            }

            DB::commit();

            return response()->json([
                'status'         => 'success',
                'message'        => 'Buyurtma muvaffaqiyatli yaratildi',
                'order_id'       => $purchase->id,
                'payment_status' => $purchase->fresh()->paymentStatus,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase failed', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->err('Buyurtma yaratishda xatolik yuz berdi!', 500);
        }
    }

    // =========================================================================
    //  BUYURTMANI BEKOR QILISH
    // =========================================================================

    public function cancelOrder(Request $request, $orderId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $order = Sold::where('id', $orderId)->where('user_id', $user->id)->first();
        if (!$order) return $this->err('Buyurtma topilmadi!', 404);

        $result = $this->orderService->cancelOrder($order, strict: true);
        if (!$result['ok']) return $this->err($result['message'], 400);

        return response()->json(['status' => 'success', 'message' => $result['message']]);
    }

    public function cashbackHistory(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);

        $history = \App\Models\CashbackHistory::query()
            ->where('user_id', $user->id)
            ->with(['order:id,qr,status,amount'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'balance' => (int) ($user->cashback ?? 0),
            'data' => $history->getCollection()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'action' => $item->action,
                    'amount' => (int) $item->amount,
                    'balance_before' => (int) $item->balance_before,
                    'balance_after' => (int) $item->balance_after,
                    'order' => $item->order ? [
                        'id' => $item->order->id,
                        'qr' => $item->order->qr,
                        'status' => $item->order->status,
                        'amount' => (int) $item->order->amount,
                    ] : null,
                    'meta' => $item->meta,
                    'created_at' => optional($item->created_at)?->toIso8601String(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    // =========================================================================
    //  QOLGAN METODLAR
    // =========================================================================

    public function checkLocation(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi mavjud emas!', 401);

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) return $this->err('Asosiy manzil belgilanmagan!', 400);

        return response()->json(['status' => 'success', 'data' => [
            'location'             => $location->id,
            'location_lat'         => $location->lat,
            'location_lon'         => $location->lon,
            'location_fullAddress' => $location->fullAddress,
        ]]);
    }

    public function delivery_service(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Unauthorized', 401);

        $location   = DB::table('locations')->where('id', $user->mainAddressID)->first();
        $isTashkent = str_contains(mb_strtolower($location?->fullAddress ?? ''), 'toshkent');

        $services = DeliveryService::active()
            ->forCountry('uzbekistan')
            ->forRegion($isTashkent)
            ->get();

        return response()->json(['status' => 'success', 'data' => $services]);
    }

    public function purchaseList(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $request->validate([
            'status'   => 'nullable|string|in:A,P,B,C,F',
            'from'     => 'nullable|date_format:Y-m-d',
            'to'       => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Sold::where('user_id', $user->id);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('from'))   $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))     $query->whereDate('created_at', '<=', $request->to);

        $perPage   = (int)$request->input('per_page', 15);
        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(function ($order) {
            $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
            $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
            return $order;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $paginated->items(),
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function purchaseDetails(Request $request, $order_id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->err('Foydalanuvchi topilmadi!', 401);

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (!$order) return $this->err('Buyurtma topilmadi!', 404);

        $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
        $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');

        return response()->json(['status' => 'success', 'data' => [$order]]);
    }
}
