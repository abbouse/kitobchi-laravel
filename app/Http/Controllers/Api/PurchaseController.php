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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    // =========================================================================
    //  YORDAMCHI METODLAR
    // =========================================================================

    private function errorResponse(string $message, int $status = 400)
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }

    private function getProductDiscountPrice($product)
    {
        if ($product instanceof Books) {
            return (is_numeric($product->discountPrice) && $product->discountPrice > 0)
                ? $product->discountPrice : $product->price;
        }
        if ($product instanceof Stationery) {
            return (is_numeric($product->discount_price) && $product->discount_price > 0)
                ? $product->discount_price : $product->price;
        }
        return 0;
    }

    private function getAvailableStock($cartItem)
    {
        $variant = $cartItem->variant;
        $product = $cartItem->product;
        if ($variant)                       return $variant->stock ?? 0;
        if ($product instanceof Books)      return $product->count ?? 0;
        if ($product instanceof Stationery) return $product->stock ?? 0;
        return 0;
    }

    private function decrementStock(array $data)
    {
        $product  = $data['product'];
        $variant  = $data['variant'] ?? null;
        $quantity = $data['quantity'];

        if ($variant) {
            $variant->decrement('stock', $quantity);
            if ($variant->stock < 0) { $variant->stock = 0; $variant->save(); }
        } else {
            if ($product instanceof Books) {
                $product->decrement('count', $quantity);
                if ($product->count < 0) { $product->count = 0; $product->save(); }
            } elseif ($product instanceof Stationery) {
                $product->decrement('stock', $quantity);
                if ($product->stock < 0) { $product->stock = 0; $product->save(); }
            }
        }
    }

    private function incrementStock(array $item)
    {
        $type      = $item['type'] ?? 'book';
        $productId = $item['item_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity  = $item['count_item'];

        if ($type === 'book') {
            $product = Books::find($productId);
            if ($product) $product->increment('count', $quantity);
        } elseif ($type === 'stationery') {
            $product = Stationery::find($productId);
            if ($product) {
                if ($variantId) {
                    $variant = StationeryVariant::find($variantId);
                    if ($variant) $variant->increment('stock', $quantity);
                } else {
                    $product->increment('stock', $quantity);
                }
            }
        }
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRUVI — MARKAZIY METOD
    //
    //  Mantiq:
    //    - promo.user_id = NULL  → hamma ishlatishi mumkin (agar history yo'q bo'lsa)
    //    - promo.user_id = X     → faqat X user ishlatishi mumkin, boshqa user → xato
    //    - PromocodeHistory → bir user bir promoni ikki marta ishlatib bo'lmaydi
    // =========================================================================

    /**
     * Promokodni tekshirib, discount summasini qaytaradi.
     * Xato bo'lsa — ['error' => '...'] qaytaradi.
     * Muvaffaqiyatli bo'lsa — ['discount' => int, 'promo' => object] qaytaradi.
     */
    private function validatePromocode(string $rawCode, int $userId, float $orderTotal): array
    {
        $code  = strtoupper(trim($rawCode));
        $promo = DB::table('promocodes')->where('code', $code)->first();

        // 1. Mavjudligi va holati
        if (!$promo) {
            return ['error' => 'Promokod topilmadi!'];
        }
        if ($promo->status != 1) {
            return ['error' => 'Promokod faol emas!'];
        }
        if ($promo->usedCount >= $promo->usesLimit) {
            return ['error' => 'Promokod ishlatish limiti tugagan!'];
        }

        // 2. Muddati
        if ($promo->expires_at && now()->gt($promo->expires_at)) {
            return ['error' => 'Promokod muddati tugagan!'];
        }

        // 3. USER_ID TEKSHIRUVI — asosiy yangilik
        //    user_id NULL → hammaga ochiq
        //    user_id bor  → faqat o'sha userga
        if (!is_null($promo->user_id) && (int)$promo->user_id !== $userId) {
            return ['error' => 'Bu promokod sizga tegishli emas!'];
        }

        // 4. Takror ishlatish tekshiruvi (history orqali)
        $alreadyUsed = PromocodeHistory::where('user_id', $userId)
            ->where('promocode_id', $promo->id)
            ->exists();
        if ($alreadyUsed) {
            return ['error' => 'Siz ushbu promokoddan allaqachon foydalangansiz!'];
        }

        // 5. Minimal buyurtma summa
        if ($promo->min_order_amount && $orderTotal < $promo->min_order_amount) {
            return ['error' => "Promokod faqat {$promo->min_order_amount} so'mdan yuqori buyurtmalarga amal qiladi."];
        }

        // 6. Chegirma hisoblash
        $discount = match ($promo->type) {
            'uzs'     => (int) min($promo->amount, $orderTotal),
            'percent' => (int) round(($promo->amount / 100) * $orderTotal),
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
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) return $this->errorResponse("Iltimos, asosiy manzilni belgilang!", 400);

        $cartItems = MyCart::where('user_id', $user->id)->with(['product', 'variant'])->get();
        if ($cartItems->isEmpty()) return $this->errorResponse('Savatcha bo\'sh!', 400);

        $totalItemsCount  = 0;
        $priceBeforePromo = 0;
        $uniqueSellerIds  = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->seller_id) continue;

            $usedPrice         = $this->getProductDiscountPrice($product);
            $priceBeforePromo += $usedPrice * $cartItem->count_item;
            $totalItemsCount  += $cartItem->count_item;
            $uniqueSellerIds[$product->seller_id] = true;
        }

        $sellerCount = count($uniqueSellerIds);

        $regionFromAddress = null;
        $parts = explode(',', $location->fullAddress);
        if (isset($parts[1])) $regionFromAddress = trim($parts[1]);

        $deliveryServices = DeliveryService::where('type', 'on')
            ->when(
                $regionFromAddress === 'Toshkent',
                fn($q) => $q->where('deliveryRegion', 'tash'),
                fn($q) => $q->where('deliveryRegion', 'all')
            )->get();

        $formattedDeliveryServices = $deliveryServices->map(function ($service) use ($priceBeforePromo, $sellerCount) {
            $basePrice        = $service->priceKg;
            $additionalPrice  = $basePrice * 0.5 * max(0, $sellerCount - 1);
            $calculatedPrice  = $basePrice + $additionalPrice;
            $deliveryPrice    = $priceBeforePromo >= $service->freePriceFrom ? 0 : $calculatedPrice;

            return [
                'id'              => $service->id,
                'name'            => $service->name,
                'muddat'          => $service->muddat,
                'priceKg'         => $service->priceKg,
                'freePriceFrom'   => $service->freePriceFrom,
                'is_free'         => $deliveryPrice == 0,
                'calculated_price'=> $deliveryPrice,
                'delivery_region' => $service->deliveryRegion,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'location'          => [
                    'id'          => $location->id,
                    'lat'         => $location->lat,
                    'lon'         => $location->lon,
                    'fullAddress' => $location->fullAddress,
                ],
                'cart_info'         => [
                    'items_count'       => $totalItemsCount,
                    'seller_count'      => $sellerCount,
                    'price_before_promo'=> $priceBeforePromo,
                    'cashback_balance'  => $user->cashback ?? 0,
                ],
                'delivery_services' => $formattedDeliveryServices,
            ]
        ]);
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRISH ENDPOINT
    // =========================================================================

    public function checkPromo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        $code = trim($request->query('code', ''));
        if (!$code) return $this->errorResponse('Promokod kiritilmadi!', 400);

        $cartItems = MyCart::where('user_id', $user->id)->with(['product', 'variant'])->get();
        if ($cartItems->isEmpty()) return $this->errorResponse('Savatcha bo\'sh!', 400);

        $totalSum = $cartItems->sum(
            fn($item) => $this->getProductDiscountPrice($item->product) * $item->count_item
        );

        $result = $this->validatePromocode($code, $user->id, $totalSum);

        if (isset($result['error'])) {
            return $this->errorResponse($result['error'], 400);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => 'Promokod yaroqli',
            'discount'    => $result['discount'],
            'final_price' => max(0, $totalSum - $result['discount']),
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
            'gift_id'            => 'nullable|integer|exists:gifts,id',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        DB::beginTransaction();
        try {
            $buyerWish       = Str::limit(trim(strip_tags($request->input('buyerWish', ''))), 300);
            $cartItems       = MyCart::where('user_id', $user->id)->with(['product', 'variant'])->get();
            $location        = DB::table('locations')->where('id', $user->mainAddressID)->first();
            $deliveryService = DeliveryService::findOrFail($request->deliveryservice_id);

            if ($cartItems->isEmpty() || !$location) {
                return $this->errorResponse('Ma\'lumotlar yetarli emas!', 400);
            }

            // ── Mahsulotlarni tayyorlash ──────────────────────────────────────
            $groupedBySeller  = [];
            $allItems         = [];
            $uniqueSellerIds  = [];
            $totalSum         = 0;
            $productsToUpdate = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                if (!$product || !$product->seller_id || !Seller::find($product->seller_id)) continue;

                $sellerId        = $product->seller_id;
                $quantity        = $cartItem->count_item;
                $usedPrice       = $this->getProductDiscountPrice($product);
                $availableStock  = $this->getAvailableStock($cartItem);

                if ($quantity > $availableStock) {
                    DB::rollBack();
                    return $this->errorResponse("{$product->name} uchun yetarli zaxira yo'q! Mavjud: {$availableStock}", 400);
                }

                $groupedBySeller[$sellerId][]           = $cartItem;
                $uniqueSellerIds[$sellerId]              = true;
                $totalSum                               += $usedPrice * $quantity;

                $image  = $variant?->image_path ?? ($product->images[0] ?? null);
                $item   = [
                    'name'       => $product->name,
                    'item_price' => $usedPrice,
                    'item_id'    => $product->id,
                    'count_item' => $quantity,
                    'seller_id'  => $sellerId,
                    'type'       => $cartItem->product_type,
                    'cover'      => $image,
                ];

                if ($cartItem->product_type === 'book')          $item['author']   = $product->author;
                if ($cartItem->product_type === 'stationery') {
                    $item['material'] = $product->material;
                    if ($variant) { $item['variant_id'] = $variant->id; $item['color_name'] = $variant->color_name; }
                }

                $allItems[]         = $item;
                $productsToUpdate[] = [
                    'product'  => $product,
                    'variant'  => $variant,
                    'quantity' => $quantity,
                    'revenue'  => $usedPrice * $quantity,
                    'user_id'  => $user->id,
                    'type'     => $cartItem->product_type,
                ];
            }

            if (empty($allItems)) return $this->errorResponse('Savatchada mahsulot topilmadi!', 400);

            $priceBeforePromo = $totalSum;

            // ── Promokod qo'llash ─────────────────────────────────────────────
            $discountAmount = 0;
            $appliedPromo   = null;
            $appliedPromoId = null;

            if ($request->filled('promocode')) {
                $result = $this->validatePromocode($request->promocode, $user->id, $priceBeforePromo);

                if (isset($result['error'])) {
                    DB::rollBack();
                    return $this->errorResponse($result['error'], 400);
                }

                $discountAmount = $result['discount'];
                $appliedPromo   = $result['promo']->code;
                $appliedPromoId = $result['promo']->id;
                $totalSum       = max(0, $priceBeforePromo - $discountAmount);
            }

            // ── Yetkazib berish narxi ─────────────────────────────────────────
            $sellerCount            = count($uniqueSellerIds);
            $baseDeliveryPrice      = $deliveryService->priceKg;
            $calculatedDelivery     = $baseDeliveryPrice + ($baseDeliveryPrice * 0.5 * max(0, $sellerCount - 1));
            $deliveryPrice          = $priceBeforePromo >= $deliveryService->freePriceFrom ? 0 : $calculatedDelivery;

            // ── Cashback ──────────────────────────────────────────────────────
            $amountBeforeCashback = $totalSum + $deliveryPrice;
            $useCashback          = $request->boolean('withCashback');
            $cashbackBalance      = $user->cashback ?? 0;
            $cashbackUsed         = 0;
            $finalPrice           = $amountBeforeCashback;

            if ($useCashback && $cashbackBalance > 0 && $amountBeforeCashback > 0 && $request->paymentStatus == 1) {
                $cashbackUsed = min($cashbackBalance, $amountBeforeCashback);
                $finalPrice   = max(0, $amountBeforeCashback - $cashbackUsed);
                $user->cashback -= $cashbackUsed;
                $user->save();
            }

            // ── Gift ──────────────────────────────────────────────────────────
            $giftId = $request->input('gift_id');
            $gift   = null;

            if ($giftId) {
                $gift = Gifts::find($giftId);
                if (!$gift) { DB::rollBack(); return $this->errorResponse('Gift topilmadi!', 400); }

                if ($gift->seller_id == 1) {
                    $allItems[]         = ['name' => $gift->name, 'item_price' => 0, 'item_id' => $gift->id, 'count_item' => 1, 'seller_id' => 1, 'type' => 'gift', 'cover' => $gift->images[0] ?? null];
                    $productsToUpdate[] = ['product' => $gift, 'variant' => null, 'quantity' => 1, 'revenue' => 0, 'user_id' => $user->id, 'type' => 'gift'];
                    $gift->decrement('stock', 1);
                    if ($gift->stock < 0) { $gift->stock = 0; $gift->save(); }
                }
            } else {
                $giftId = 1;
            }

            // ── Asosiy buyurtma ───────────────────────────────────────────────
            $locationData = [
                'fullName'    => $user->name . ' ' . $user->lastname,
                'fullAddress' => $location->fullAddress,
                'lat'         => $location->lat,
                'lon'         => $location->lon,
                'phoneNumber' => $user->phone_number,
            ];

            $purchase = Sold::create([
                'user_id'        => $user->id,
                'qr'             => Str::random(40),
                'items'          => $allItems,
                'address'        => [$locationData],
                'deliveryType'   => $deliveryService->name,
                'deliveryPrice'  => $deliveryPrice,
                'paymentStatus'  => $request->paymentStatus == true ? 1 : 0,
                'amount'         => $finalPrice,
                'gift'           => $giftId,
                'buyerWish'      => $buyerWish,
                'promocode'      => $appliedPromo,
                'discountAmount' => $discountAmount,
                'withCashback'   => $useCashback && $cashbackUsed > 0,
                'cashbackAmount' => $cashbackUsed,
            ]);

            // ── Seller va Courier orderlar ────────────────────────────────────
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
                    $price         = $this->getProductDiscountPrice($product);
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
                $sellerOrder->update(['amount' => $sellerAmount]);
            }

            $courierOrder = CourierOrder::create([
                'courier_id'   => null,
                'order_id'     => $purchase->id,
                'user_id'      => $user->id,
                'amount'       => $totalSum,
                'status'       => $request->paymentStatus == 1 ? 'pay_process' : 'pending',
                'courierPrice' => $deliveryPrice,
            ]);

            foreach ($allItems as $itm) {
                if ($itm['type'] === 'gift') continue;

                $sellerLocation = DB::table('seller_locations')
                    ->where('seller_id', $itm['seller_id'])
                    ->where('is_main', true)
                    ->first();

                if (!$sellerLocation) {
                    Log::error("Seller location not found for seller_id: {$itm['seller_id']}");
                    continue;
                }

                CourierOrderItem::create([
                    'seller_id'          => $itm['seller_id'],
                    'seller_location_id' => $sellerLocation->id,
                    'order_id'           => $courierOrder->id,
                    'type'               => $itm['type'],
                    'product_id'         => $itm['item_id'],
                    'quantity'           => $itm['count_item'],
                    'price'              => $itm['item_price'],
                    'variant_id'         => $itm['variant_id'] ?? null,
                ]);
            }

            // ── Statistika va stock yangilash ─────────────────────────────────
            foreach ($productsToUpdate as $data) {
                $product  = $data['product'];
                $quantity = $data['quantity'];
                $revenue  = $data['revenue'];
                $userId   = $data['user_id'];
                $type     = $data['type'] ?? 'book';

                $product->increment('totalSales', $quantity);
                $product->increment('totalRevenue', $revenue);
                $product->increment('totalSalesWeek', $quantity);
                $product->increment('totalRevenueWeek', $revenue);

                $hasPrev = Sold::where('user_id', $userId)
                    ->where('id', '!=', $purchase->id)
                    ->where(function ($query) use ($product, $type) {
                        if ($type === 'gift') $query->where('gift', $product->id);
                        else $query->whereJsonContains('items', ['item_id' => $product->id, 'type' => $type]);
                    })->exists();

                if (!$hasPrev) {
                    $product->increment('totalClients');
                    $product->increment('totalClientsWeek');
                }

                $product->save();

                if ($type !== 'gift') $this->decrementStock($data);
            }

            // ── Promokod tarixi va counter ────────────────────────────────────
            if ($appliedPromoId) {
                PromocodeHistory::create(['user_id' => $user->id, 'promocode_id' => $appliedPromoId]);
                DB::table('promocodes')->where('id', $appliedPromoId)->increment('usedCount');
            }

            MyCart::where('user_id', $user->id)->delete();
            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Buyurtma muvaffaqiyatli yaratildi',
                'order_id' => $purchase->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return $this->errorResponse('Buyurtma yaratishda xatolik yuz berdi!', 500);
        }
    }

    // =========================================================================
    //  BUYURTMANI BEKOR QILISH
    // =========================================================================

    public function cancelOrder(Request $request, $orderId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        DB::beginTransaction();
        try {
            $order = Sold::where('id', $orderId)->where('user_id', $user->id)->first();
            if (!$order) return $this->errorResponse('cancel_order_error', 404);

            $createdAt = Carbon::parse($order->created_at);
            if (now()->gt($createdAt->addHour()) || !in_array($order->paymentStatus, [0, 1])) {
                return $this->errorResponse('cancel_order_error_expired', 400);
            }

            $order->status        = 'F';
            $order->paymentStatus = 3;
            $order->save();

            // Stock qaytarish
            foreach ($order->items ?? [] as $item) {
                if ($item['type'] === 'gift') continue;
                $this->incrementStock($item);
            }

            // Gift stock qaytarish
            if ($order->gift) {
                $gift = Gifts::find($order->gift);
                if ($gift && $gift->seller_id == 1) $gift->increment('stock', 1);
            }

            // Statistika minus
            foreach ($order->items ?? [] as $item) {
                if ($item['type'] === 'gift') continue;
                $productId = $item['item_id'];
                $quantity  = $item['count_item'];
                $revenue   = $item['item_price'] * $quantity;
                $type      = $item['type'];

                $product = $type === 'book' ? Books::find($productId) : Stationery::find($productId);
                if ($product) {
                    $product->decrement('totalSales', $quantity);
                    $product->decrement('totalRevenue', $revenue);
                    $product->decrement('totalSalesWeek', $quantity);
                    $product->decrement('totalRevenueWeek', $revenue);
                    $product->totalSales        = max(0, $product->totalSales);
                    $product->totalRevenue      = max(0, $product->totalRevenue);
                    $product->totalSalesWeek    = max(0, $product->totalSalesWeek);
                    $product->totalRevenueWeek  = max(0, $product->totalRevenueWeek);
                    $product->save();
                }
            }

            // Gift statistika minus
            if ($order->gift) {
                $gift = Gifts::find($order->gift);
                if ($gift && $gift->seller_id == 1) {
                    $gift->decrement('totalSales', 1);
                    $gift->decrement('totalSalesWeek', 1);
                    $gift->totalSales       = max(0, $gift->totalSales);
                    $gift->totalSalesWeek   = max(0, $gift->totalSalesWeek);
                    $gift->save();
                }
            }

            // Cashback qaytarish
            if ($order->withCashback && $order->cashbackAmount > 0) {
                $user->increment('cashback', $order->cashbackAmount);
            }

            // Promokod qaytarish
            if ($order->promocode) {
                $promo = DB::table('promocodes')->where('code', $order->promocode)->first();
                if ($promo) {
                    DB::table('promocodes')->where('id', $promo->id)->decrement('usedCount');
                    PromocodeHistory::where('user_id', $user->id)->where('promocode_id', $promo->id)->delete();
                }
            }

            SellerOrder::where('order_id', $order->id)->update(['status' => 3]);
            CourierOrder::where('order_id', $order->id)->update(['status' => 'rejected']);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'order_canceled']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation failed', ['order_id' => $orderId, 'message' => $e->getMessage()]);
            return $this->errorResponse('cancel_order_error', 500);
        }
    }

    // =========================================================================
    //  QOLGAN METODLAR
    // =========================================================================

    public function checkLocation(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi mavjud emas!', 401);

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) return $this->errorResponse('Asosiy manzil belgilanmagan!', 400);

        return response()->json(['status' => 'success', 'data' => [
            'location'             => $location->id,
            'location_lat'         => $location->lat,
            'location_lon'         => $location->lon,
            'location_fullAddress' => $location->fullAddress,
        ]]);
    }

    public function delivery_service(Request $request)
    {
        $user = User::where('remember_token', $request->bearerToken())->first();
        if (!$user) return $this->errorResponse('Unauthorized', 401);

        $addressRow = Locations::where('user_id', $user->id)->where('id', $user->mainAddressID)->first();
        $region     = $addressRow?->fullAddress ? trim(explode(',', $addressRow->fullAddress)[1] ?? '') : null;

        $services = DeliveryService::where('type', 'on')
            ->when($region === 'Toshkent', fn($q) => $q->where('deliveryRegion', 'tash'), fn($q) => $q->where('deliveryRegion', 'all'))
            ->get();

        return response()->json(['status' => 'success', 'data' => $services]);
    }

    public function purchaseList(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        $orders = Sold::where('user_id', $user->id)->latest()->get()->map(function ($order) {
            $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
            $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
            return $order;
        });

        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function purchaseDetails(Request $request, $order_id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return $this->errorResponse('Foydalanuvchi topilmadi!', 401);

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (!$order) return $this->errorResponse('Buyurtma topilmadi!', 404);

        $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
        $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');

        return response()->json(['status' => 'success', 'data' => [$order]]);
    }
}