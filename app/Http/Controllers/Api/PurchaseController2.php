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
    /** Standart xato javobini qaytarish uchun yordamchi metod */
    private function errorResponse(string $message, int $status = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $status);
    }

    /** Mahsulotning chegirmali narxini qaytaradi */
    private function getProductDiscountPrice($product)
    {
        if ($product instanceof Books) {
            return (is_numeric($product->discountPrice) && $product->discountPrice > 0)
                ? $product->discountPrice
                : $product->price;
        }
        if ($product instanceof Stationery) {
            return (is_numeric($product->discount_price) && $product->discount_price > 0)
                ? $product->discount_price
                : $product->price;
        }
        return 0;
    }

    /** Mavjud zaxirani (stock) qaytaradi — variant yoki asosiy mahsulotga qarab */
    private function getAvailableStock($cartItem)
    {
        $variant = $cartItem->variant;
        $product = $cartItem->product;

        if ($variant) {
            return $variant->stock ?? 0;
        }
        if ($product instanceof Books) {
            return $product->count ?? 0;
        }
        if ($product instanceof Stationery) {
            return $product->stock ?? 0;
        }
        return 0;
    }

    /** Buyurtma tasdiqlanganda zaxiradan (stock) ayirish */
    private function decrementStock(array $data)
    {
        $product = $data['product'];
        $variant = $data['variant'] ?? null;
        $quantity = $data['quantity'];

        if ($variant) {
            $variant->decrement('stock', $quantity);
            if ($variant->stock < 0) {
                $variant->stock = 0;
                $variant->save();
            }
        } else {
            if ($product instanceof Books) {
                $product->decrement('count', $quantity);
                if ($product->count < 0) {
                    $product->count = 0;
                    $product->save();
                }
            } elseif ($product instanceof Stationery) {
                $product->decrement('stock', $quantity);
                if ($product->stock < 0) {
                    $product->stock = 0;
                    $product->save();
                }
            }
        }
    }

    /** Buyurtma bekor qilinganda zaxirani (stock) qaytarish */
    private function incrementStock(array $item)
    {
        $type = $item['type'] ?? 'book';
        $productId = $item['item_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity = $item['count_item'];

        if ($type === 'book') {
            $product = Books::find($productId);
            if ($product) {
                $product->increment('count', $quantity);
            }
        } elseif ($type === 'stationery') {
            $product = Stationery::find($productId);
            if ($product) {
                if ($variantId) {
                    $variant = StationeryVariant::find($variantId);
                    if ($variant) {
                        $variant->increment('stock', $quantity);
                    }
                } else {
                    $product->increment('stock', $quantity);
                }
            }
        }
    }

    /** Checkout sahifasi uchun kerakli maʼlumotlarni qaytaradi */
    public function getCartCheckoutInfo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) {
            return $this->errorResponse("Iltimos, asosiy manzilni belgilang!", 400);
        }

        $cartItems = MyCart::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->get();

        $totalItemsCount = 0;
        $priceBeforePromo = 0;
        $uniqueSellerIds = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if (!$product || !$product->seller_id) {
                continue;
            }

            $usedPrice = $this->getProductDiscountPrice($product);
            $priceBeforePromo += $usedPrice * $cartItem->count_item;
            $totalItemsCount += $cartItem->count_item;
            $uniqueSellerIds[$product->seller_id] = true;
        }

        $sellerCount = count($uniqueSellerIds);

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Savatcha boʻsh!', 400);
        }

        // Yetkazib berish xizmati uchun mintaqa aniqlash
        $regionFromAddress = null;
        $parts = explode(',', $location->fullAddress);
        if (isset($parts[1])) {
            $regionFromAddress = trim($parts[1]);
        }

        $deliveryServices = DeliveryService::where('type', 'on')
            ->when($regionFromAddress === 'Toshkent', fn($q) => $q->where('deliveryRegion', 'tash'), fn($q) => $q->where('deliveryRegion', 'all'))
            ->get();

        $formattedDeliveryServices = $deliveryServices->map(function ($service) use ($priceBeforePromo, $sellerCount) {
            $basePrice = $service->priceKg;
            $additionalPrice = $basePrice * 0.5 * max(0, $sellerCount - 1);
            $calculatedPrice = $basePrice + $additionalPrice;
            $deliveryPrice = $priceBeforePromo >= $service->freePriceFrom ? 0 : $calculatedPrice;

            return [
                'id' => $service->id,
                'name' => $service->name,
                'muddat' => $service->muddat,
                'priceKg' => $service->priceKg,
                'freePriceFrom' => $service->freePriceFrom,
                'is_free' => $deliveryPrice == 0,
                'calculated_price' => $deliveryPrice,
                'delivery_region' => $service->deliveryRegion,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => [
                    'id' => $location->id,
                    'lat' => $location->lat,
                    'lon' => $location->lon,
                    'fullAddress' => $location->fullAddress,
                ],
                'cart_info' => [
                    'items_count' => $totalItemsCount,
                    'seller_count' => $sellerCount,
                    'price_before_promo' => $priceBeforePromo,
                    'cashback_balance' => $user->cashback ?? 0,
                ],
                'delivery_services' => $formattedDeliveryServices,
            ]
        ], 200);
    }

    /** Buyurtma yaratish (kitob va stationery uchun umumiy) */
    public function buy_book(Request $request)
    {
        $request->validate([
            'paymentStatus' => 'required|boolean',
            'buyerWish' => 'nullable|string|max:300',
            'promocode' => 'nullable|string|max:50',
            'deliveryservice_id' => 'required|integer|exists:delivery_services,id',
            'withCashback' => 'nullable|boolean',
            'gift_id' => 'nullable|integer|exists:gifts,id',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        DB::beginTransaction();
        try {
            $buyerWish = Str::limit(trim(strip_tags($request->input('buyerWish', ''))), 300);

            $cartItems = MyCart::where('user_id', $user->id)->with(['product', 'variant'])->get();
            $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
            $deliveryService = DeliveryService::findOrFail($request->deliveryservice_id);

            if ($cartItems->isEmpty() || !$location) {
                return $this->errorResponse('Maʼlumotlar yetarli emas!', 400);
            }

            // ===================================================================
            // Maʼlumotlarni tayyorlash va oxirgi stock tekshiruvi
            // ===================================================================
            $groupedBySeller = [];
            $allItems = [];
            $uniqueSellerIds = [];
            $totalSum = 0; // Promokoddan oldingi chegirmali narxlar summasi
            $productsToUpdate = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                if (!$product || !$product->seller_id || !Seller::find($product->seller_id)) {
                    continue;
                }

                $sellerId = $product->seller_id;
                $quantity = $cartItem->count_item;
                $usedPrice = $this->getProductDiscountPrice($product);

                // Oxirgi stock tekshiruvi
                $availableStock = $this->getAvailableStock($cartItem);
                if ($quantity > $availableStock) {
                    return $this->errorResponse("{$product->name} uchun yetarli zaxira yoʻq! Mavjud: {$availableStock}", 400);
                }

                $groupedBySeller[$sellerId][] = $cartItem;
                $uniqueSellerIds[$sellerId] = true;
                $totalSum += $usedPrice * $quantity;

                // Sold jadvaliga saqlanadigan item maʼlumotlari
                $image = $variant?->image_path ?? ($product->images[0] ?? null);
                $item = [
                    'name' => $product->name,
                    'item_price' => $usedPrice,
                    'item_id' => $product->id,
                    'count_item' => $quantity,
                    'seller_id' => $sellerId,
                    'type' => $cartItem->product_type,
                    'cover' => $image,
                ];

                if ($cartItem->product_type === 'book') {
                    $item['author'] = $product->author;
                } elseif ($cartItem->product_type === 'stationery') {
                    $item['material'] = $product->material;
                    if ($variant) {
                        $item['variant_id'] = $variant->id;
                        $item['color_name'] = $variant->color_name;
                    }
                }

                $allItems[] = $item;

                $productsToUpdate[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'revenue' => $usedPrice * $quantity,
                    'user_id' => $user->id,
                    'type' => $cartItem->product_type,
                ];
            }

            if (empty($allItems)) {
                return $this->errorResponse('Savatchada mahsulot topilmadi!', 400);
            }

            $priceBeforePromo = $totalSum;

            // ===================================================================
            // Promokod qoʻllash
            // ===================================================================
            $discountAmount = 0;
            $appliedPromo = null;
            $appliedPromoId = null;

            if ($request->filled('promocode')) {
                $promoCode = strtoupper(trim($request->promocode));
                $promo = DB::table('promocodes')->where('code', $promoCode)->first();

                if (!$promo || $promo->status != 1 || $promo->usedCount >= $promo->usesLimit) {
                    return $this->errorResponse('Promokod yaroqsiz yoki muddati tugagan!', 400);
                }

                if (PromocodeHistory::where('user_id', $user->id)->where('promocode_id', $promo->id)->exists()) {
                    return $this->errorResponse('Siz ushbu promokoddan allaqachon foydalangansiz!', 400);
                }

                if ($priceBeforePromo < $promo->min_order_amount) {
                    return $this->errorResponse("Promokod faqat {$promo->min_order_amount} soʻmdan yuqori buyurtmalarga amal qiladi.", 400);
                }

                if ($promo->type === 'uzs') {
                    $discountAmount = min($promo->amount, $priceBeforePromo);
                } elseif ($promo->type === 'percent') {
                    $discountAmount = round(($promo->amount / 100) * $priceBeforePromo);
                }

                $totalSum = max(0, $priceBeforePromo - $discountAmount);
                $appliedPromo = $promo->code;
                $appliedPromoId = $promo->id;
            }

            // ===================================================================
            // Yetkazib berish narxini hisoblash
            // ===================================================================
            $sellerCount = count($uniqueSellerIds);
            $baseDeliveryPrice = $deliveryService->priceKg;
            $additionalPricePerSeller = $baseDeliveryPrice * 0.5;
            $calculatedDeliveryPrice = $baseDeliveryPrice + ($additionalPricePerSeller * max(0, $sellerCount - 1));
            $deliveryPrice = $priceBeforePromo >= $deliveryService->freePriceFrom ? 0 : $calculatedDeliveryPrice;

            // ===================================================================
            // Keshbek qoʻllash
            // ===================================================================
            $amountBeforeCashback = $totalSum + $deliveryPrice;
            $useCashback = $request->boolean('withCashback');
            $cashbackBalance = $user->cashback ?? 0;
            $cashbackUsed = 0;
            $finalPrice = $amountBeforeCashback;

            if ($useCashback && $cashbackBalance > 0 && $amountBeforeCashback > 0 && $request->paymentStatus == 1) {
                $cashbackUsed = min($cashbackBalance, $amountBeforeCashback);
                $finalPrice = max(0, $amountBeforeCashback - $cashbackUsed);
                $user->cashback -= $cashbackUsed;
                $user->save();
            }

            // ===================================================================
            // Gift qo'shish logikasi
            // ===================================================================
            $giftId = $request->input('gift_id');
            $gift = null;

            if ($giftId) {
                $gift = Gifts::find($giftId);
                if (!$gift) {
                    return $this->errorResponse('Gift topilmadi!', 400);
                }

                // Platforma gift (seller_id == 1) → 0 so'm bilan qo'shamiz
                if ($gift->seller_id == 1) {
                    $giftItem = [
                        'name' => $gift->name,
                        'item_price' => 0,
                        'item_id' => $gift->id,
                        'count_item' => 1,
                        'seller_id' => 1,
                        'type' => 'gift',
                        'cover' => $gift->images[0] ?? null,
                    ];
                    $allItems[] = $giftItem;

                    // Statistika va stock uchun qo'shamiz
                    $productsToUpdate[] = [
                        'product' => $gift,
                        'variant' => null,
                        'quantity' => 1,
                        'revenue' => 0,
                        'user_id' => $user->id,
                        'type' => 'gift',
                    ];

                    // Gift stockdan ayirish
                    $gift->decrement('stock', 1);
                    if ($gift->stock < 0) {
                        $gift->stock = 0;
                        $gift->save();
                    }
                }
            } else {
                // Agar gift_id kelmasa — random (oldingi logika)
                $giftId = 1;
            }

            // ===================================================================
            // Asosiy buyurtma (Sold) yaratish
            // ===================================================================
            $locationData = [
                'fullName' => $user->name . ' ' . $user->lastname,
                'fullAddress' => $location->fullAddress,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'phoneNumber' => $user->phone_number,
            ];

            $purchase = Sold::create([
                'user_id' => $user->id,
                'qr' => Str::random(40),
                'items' => $allItems,
                'address' => [$locationData],
                'deliveryType' => $deliveryService->name,
                'deliveryPrice' => $deliveryPrice,
                'paymentStatus' => $request->paymentStatus == true ? 1 : 0,
                'amount' => $finalPrice,
                'gift' => $giftId,
                'buyerWish' => $buyerWish,
                'promocode' => $appliedPromo,
                'discountAmount' => $discountAmount,
                'withCashback' => $useCashback && $cashbackUsed > 0,
                'cashbackAmount' => $cashbackUsed,
            ]);

            // ===================================================================
            // Seller va Courier orderlarini yaratish (giftlarni o'tkazib yuborish)
            // ===================================================================
            foreach ($groupedBySeller as $sellerId => $items) {
                $sellerAmount = 0;
                $sellerOrder = SellerOrder::create([
                    'seller_id' => $sellerId,
                    'order_id' => $purchase->id,
                    'client_id' => $user->id,
                    'status' => $request->paymentStatus == 1 ? 0 : 1,
                    'delivery_type' => $deliveryService->name,
                    'address' => [$locationData],
                ]);

                foreach ($items as $item) {
                    $product = $item->product;
                    $quantity = $item->count_item;
                    $usedPrice = $this->getProductDiscountPrice($product);
                    $sellerAmount += $usedPrice * $quantity;

                    SellerOrderItem::create([
                        'seller_id' => $sellerId,
                        'order_id' => $sellerOrder->id,
                        'product_id' => $product->id,
                        'type' => $item->product_type,
                        'quantity' => $quantity,
                        'price' => $usedPrice,
                        'variant_id' => $item->variant_id ?? null,
                    ]);
                }

                $sellerOrder->update(['amount' => $sellerAmount]);
            }

            $courierOrder = CourierOrder::create([
                'courier_id' => null,
                'order_id' => $purchase->id,
                'user_id' => $user->id,
                'amount' => $totalSum,
                'status' => $request->paymentStatus == 1 ? 'pay_process' : 'pending',
                'courierPrice' => $deliveryPrice,
            ]);

            foreach ($allItems as $itm) {
                if ($itm['type'] === 'gift') {
                    continue;
                }

                $sellerLocation = DB::table('seller_locations')
                    ->where('seller_id', $itm['seller_id'])
                    ->where('is_main', true)
                    ->first();

                if (!$sellerLocation) {
                    Log::error("Seller location not found for seller_id: {$itm['seller_id']}");
                    continue;
                }

                CourierOrderItem::create([
                    'seller_id' => $itm['seller_id'],
                    'seller_location_id' => $sellerLocation->id,
                    'order_id' => $courierOrder->id,
                    'type' => $itm['type'],
                    'product_id' => $itm['item_id'],
                    'quantity' => $itm['count_item'],
                    'price' => $itm['item_price'],
                    'variant_id' => $itm['variant_id'] ?? null,
                ]);
            }

            // ===================================================================
            // Statistika va stock yangilash (book, stationery, gift)
            // ===================================================================
            foreach ($productsToUpdate as $data) {
                $product = $data['product'];
                $quantity = $data['quantity'];
                $revenue = $data['revenue'];
                $userId = $data['user_id'];
                $type = $data['type'] ?? 'book';

                // Umumiy statistika
                $product->increment('totalSales', $quantity);
                $product->increment('totalRevenue', $revenue);
                $product->increment('totalSalesWeek', $quantity);
                $product->increment('totalRevenueWeek', $revenue);

                // Yangi mijozni hisoblash
                $hasPreviousPurchase = Sold::where('user_id', $userId)
                    ->where('id', '!=', $purchase->id)
                    ->where(function ($query) use ($product, $type) {
                        if ($type === 'gift') {
                            $query->where('gift', $product->id);
                        } else {
                            $query->whereJsonContains('items', ['item_id' => $product->id, 'type' => $type]);
                        }
                    })
                    ->exists();

                if (!$hasPreviousPurchase) {
                    $product->increment('totalClients');
                    $product->increment('totalClientsWeek');
                }

                $product->save();

                // Stockdan ayirish (gift uchun allaqachon yuqorida ayirilgan)
                if ($type !== 'gift') {
                    $this->decrementStock($data);
                }
            }

            // Promokod tarixi
            if ($appliedPromoId) {
                PromocodeHistory::create([
                    'user_id' => $user->id,
                    'promocode_id' => $appliedPromoId,
                ]);
                DB::table('promocodes')->where('id', $appliedPromoId)->increment('usedCount');
            }

            // Savatchani tozalash
            MyCart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Buyurtma muvaffaqiyatli yaratildi',
                'order_id' => $purchase->id,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Buyurtma yaratishda xatolik yuz berdi!', 500);
        }
    }

    /** Buyurtmani bekor qilish */
    public function cancelOrder(Request $request, $orderId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        DB::beginTransaction();
        try {
            $order = Sold::where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return $this->errorResponse('Buyurtma topilmadi yoki sizga tegishli emas!', 404);
            }

            // Bekor qilish shartlari: 1 soat ichida va hali to'lanmagan bo'lsa
            $createdAt = Carbon::parse($order->created_at);
            $oneHourLater = $createdAt->addHour();
            $now = Carbon::now();

            if ($now->greaterThan($oneHourLater) || !in_array($order->paymentStatus, [0, 1])) {
                return $this->errorResponse('Buyurtmani bekor qilish muddati oʻtgan yoki status oʻzgargan!', 400);
            }

            // Statusni bekor qilish
            $order->status = "F";
            $order->paymentStatus = 3; // Rad etildi
            $order->save();

            // ===================================================================
            // Oddiy mahsulotlarni stockga qaytarish
            // ===================================================================
            foreach ($order->items ?? [] as $item) {
                if ($item['type'] === 'gift') {
                    continue;
                }
                $this->incrementStock($item);
            }

            // ===================================================================
            // Giftni stockga qaytarish (faqat platforma giftlari — seller_id = 1)
            // ===================================================================
            if ($order->gift) {
                $gift = Gifts::find($order->gift);
                if ($gift && $gift->seller_id == 1) {
                    $gift->increment('stock', 1);
                }
            }

            // ===================================================================
            // Statistika minus qilish (book, stationery, gift)
            // ===================================================================
            foreach ($order->items ?? [] as $item) {
                if ($item['type'] === 'gift') {
                    continue;
                }

                $productId = $item['item_id'];
                $quantity = $item['count_item'];
                $revenue = $item['item_price'] * $quantity;
                $type = $item['type'];

                if ($type === 'book') {
                    $product = Books::find($productId);
                } elseif ($type === 'stationery') {
                    $product = Stationery::find($productId);
                } else {
                    continue;
                }

                if ($product) {
                    $product->decrement('totalSales', $quantity);
                    $product->decrement('totalRevenue', $revenue);
                    $product->decrement('totalSalesWeek', $quantity);
                    $product->decrement('totalRevenueWeek', $revenue);

                    // Salbiy bo'lib qolmasin
                    $product->totalSales = max(0, $product->totalSales);
                    $product->totalRevenue = max(0, $product->totalRevenue);
                    $product->totalSalesWeek = max(0, $product->totalSalesWeek);
                    $product->totalRevenueWeek = max(0, $product->totalRevenueWeek);

                    $product->save();
                }
            }

            // Platforma giftini statistikadan ayirish
            if ($order->gift) {
                $gift = Gifts::find($order->gift);
                if ($gift && $gift->seller_id == 1) {
                    $gift->decrement('totalSales', 1);
                    $gift->decrement('totalRevenue', 0);
                    $gift->decrement('totalSalesWeek', 1);
                    $gift->decrement('totalRevenueWeek', 0);

                    $gift->totalSales = max(0, $gift->totalSales);
                    $gift->totalRevenue = max(0, $gift->totalRevenue);
                    $gift->totalSalesWeek = max(0, $gift->totalSalesWeek);
                    $gift->totalRevenueWeek = max(0, $gift->totalRevenueWeek);

                    $gift->save();
                }
            }

            // ===================================================================
            // Keshbek qaytarish
            // ===================================================================
            if ($order->withCashback && $order->cashbackAmount > 0) {
                $user->increment('cashback', $order->cashbackAmount);
            }

            // ===================================================================
            // Promokod qaytarish
            // ===================================================================
            if ($order->promocode) {
                $promo = DB::table('promocodes')->where('code', $order->promocode)->first();
                if ($promo) {
                    DB::table('promocodes')->where('id', $promo->id)->decrement('usedCount');
                    PromocodeHistory::where('user_id', $user->id)
                        ->where('promocode_id', $promo->id)
                        ->delete();
                }
            }

            // ===================================================================
            // Bog'liq orderlarni bekor qilish
            // ===================================================================
            SellerOrder::where('order_id', $order->id)->update(['status' => 3]);
            CourierOrder::where('order_id', $order->id)->update(['status' => 'rejected']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Buyurtma muvaffaqiyatli bekor qilindi!',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation failed', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->errorResponse('Buyurtmani bekor qilishda xatolik yuz berdi!', 500);
        }
    }

    // ===================================================================
    // Qolgan metodlar
    // ===================================================================
    public function checkLocation(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi mavjud emas!', 401);
        }

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) {
            return $this->errorResponse('Asosiy manzil belgilanmagan!', 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => $location->id,
                'location_lat' => $location->lat,
                'location_lon' => $location->lon,
                'location_fullAddress' => $location->fullAddress,
            ]
        ], 200);
    }

    public function delivery_service(Request $request)
    {
        $user = User::where('remember_token', $request->bearerToken())->first();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $addressRow = Locations::where('user_id', $user->id)
            ->where('id', $user->mainAddressID)
            ->first();

        $region = $addressRow?->fullAddress ? trim(explode(',', $addressRow->fullAddress)[1] ?? '') : null;

        $services = DeliveryService::where('type', 'on')
            ->when($region === 'Toshkent', fn($q) => $q->where('deliveryRegion', 'tash'), fn($q) => $q->where('deliveryRegion', 'all'))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $services
        ], 200);
    }

    public function checkPromo(Request $request)
    {
        $code = strtoupper(trim($request->query('code')));
        $user = Auth::guard('user')->user();

        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        if (!$code) {
            return $this->errorResponse('Promokod kiritilmadi!', 400);
        }

        $promo = DB::table('promocodes')->where('code', $code)->first();

        if (!$promo || $promo->status != 1 || $promo->usedCount >= $promo->usesLimit) {
            return $this->errorResponse('Promokod yaroqsiz yoki muddati tugagan!', 400);
        }

        if (PromocodeHistory::where('user_id', $user->id)->where('promocode_id', $promo->id)->exists()) {
            return $this->errorResponse('Siz ushbu promokoddan allaqachon foydalangansiz!', 400);
        }

        $cartItems = MyCart::where('user_id', $user->id)->with(['product', 'variant'])->get();

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Savatcha boʻsh!', 400);
        }

        $totalSum = $cartItems->sum(fn($item) => $this->getProductDiscountPrice($item->product) * $item->count_item);

        if ($totalSum < $promo->min_order_amount) {
            return $this->errorResponse("Promokod faqat {$promo->min_order_amount} soʻmdan yuqori buyurtmalarga amal qiladi.", 400);
        }

        $discount = $promo->type === 'uzs' ? min($promo->amount, $totalSum) : round(($promo->amount / 100) * $totalSum);

        return response()->json([
            'status' => 'success',
            'message' => 'Promokod yaroqli',
            'discount' => $discount,
            'final_price' => max(0, $totalSum - $discount),
        ], 200);
    }

    public function purchaseList(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        $orders = Sold::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($order) {
                $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
                $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
                return $order;
            });

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    public function purchaseDetails(Request $request, $order_id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return $this->errorResponse('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();

        if (!$order) {
            return $this->errorResponse('Buyurtma topilmadi!', 404);
        }

        $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
        $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');

        return response()->json([
            'status' => 'success',
            'data' => [$order]
        ], 200);
    }
}