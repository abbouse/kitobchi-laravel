<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourierOrderStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\DeliveryService;
use App\Models\GiftCertificate;
use App\Models\Gifts;
use App\Models\MyCart;
use App\Models\PromocodeHistory;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use App\Models\Stationery;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserCard;
use App\Services\CashbackHistoryService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\DeliveryZoneResolverService;
use App\Services\FulfillmentRoutingService;
use App\Services\OrderFinancialSnapshotService;
use App\Services\OrderRealtimeService;
use App\Services\OrderService;
use App\Services\PaylovOrderPaymentService;
use App\Services\PostalResendService;
use App\Services\ProductReviewPromptService;
use App\Services\QrTokenService;
use App\Services\UserReputationService;
use App\Support\ProductPayloadFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    private const PLATFORM_SELLER_ID = 1;

    private const DEFAULT_PLATFORM_GIFT_ID = 1;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly CashbackHistoryService $cashbackHistoryService,
        private readonly OrderRealtimeService $orderRealtimeService,
        private readonly PostalResendService $postalResendService,
        private readonly QrTokenService $qrTokenService,
        private readonly DeliveryZoneResolverService $deliveryZoneResolverService,
        private readonly FulfillmentRoutingService $fulfillmentRoutingService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly UserReputationService $userReputationService,
        private readonly PaylovOrderPaymentService $paylovOrderPaymentService,
        private readonly ProductReviewPromptService $productReviewPromptService,
        private readonly OrderFinancialSnapshotService $orderFinancialSnapshotService,
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
                ? (float) $product->discountPrice
                : (float) $product->price;
        }
        if ($product instanceof Stationery) {
            return (is_numeric($product->discount_price) && $product->discount_price > 0)
                ? (float) $product->discount_price
                : (float) $product->price;
        }

        return 0;
    }

    private function effectiveCartItemUnitPrice(MyCart $cartItem): float
    {
        if (is_numeric($cartItem->priceItem) && (float) $cartItem->priceItem >= 0) {
            return (float) $cartItem->priceItem;
        }

        return $this->effectivePrice($cartItem->product);
    }

    private function sellerFacingCartItemUnitPrice(MyCart $cartItem): float
    {
        return $this->effectivePrice($cartItem->product);
    }

    private function collectionCheckoutMeta(Request $request): array
    {
        $meta = $request->attributes->get('collection_checkout_meta');

        return is_array($meta) ? $meta : [];
    }

    // ── Mavjud zaxira ──────────────────────────────────────────
    private function availableStock($cartItem): int
    {
        $variant = $cartItem->variant;
        $product = $cartItem->product;
        if ($variant) {
            return (int) ($variant->stock ?? 0);
        }
        if ($product instanceof Books) {
            return (int) ($product->count ?? 0);
        }
        if ($product instanceof Stationery) {
            return (int) ($product->stock ?? 0);
        }

        return 0;
    }

    // ── Defensiv rasm chiqarish ────────────────────────────────
    // images ustuni JSON cast bilan array bo'lishi kutiladi, lekin DB'da
    // string yoki null bo'lishi mumkin. Birinchi rasmni xavfsiz olamiz.
    private function pickFirstImage($images): ?string
    {
        if (is_array($images) && ! empty($images)) {
            $first = reset($images);

            return is_string($first) ? $first : null;
        }
        if (is_string($images) && $images !== '') {
            // ehtimol JSON string sifatida saqlangan
            $decoded = json_decode($images, true);
            if (is_array($decoded) && ! empty($decoded)) {
                $first = reset($decoded);

                return is_string($first) ? $first : null;
            }

            return $images; // oddiy URL string holi
        }

        return null;
    }

    private function findAvailableGiftForCheckout(int $giftId): ?Gifts
    {
        $query = Gifts::query()
            ->whereKey($giftId)
            ->whereNull('archived_at')
            ->where('status', true)
            ->where('is_approved', true)
            ->where('stock', '>', 0);

        if ($giftId === self::DEFAULT_PLATFORM_GIFT_ID) {
            $query->where('seller_id', self::PLATFORM_SELLER_ID);
        }

        return $query->lockForUpdate()->first();
    }

    // ── Breadcrumb logger ──────────────────────────────────────
    // buy_book() ichida kerakli bosqichda chaqiriladi. xatolik yuz bersa,
    // catch'da oxirgi `step` qaysi joyda bo'lganini bilamiz.
    private function trace(string $step, array $extra = []): void
    {
        Log::info("[buy_book] {$step}", $extra);
    }

    private function resolveDeliveryType(?DeliveryService $deliveryService): string
    {
        $serviceType = (string) ($deliveryService->type ?? '');

        return match ($serviceType) {
            'mail_service' => 'postal',
            'courier_service' => 'delivery',
            default => Sold::normalizeDeliveryTypeValue($deliveryService->name ?? ''),
        };
    }

    private function resolveDeliveryOffers(object $location, int $sellerCount, float|int $cartTotal)
    {
        return $this->deliveryZoneResolverService->resolveOffers($location, $sellerCount, $cartTotal);
    }

    private function applySignedDeliveryQr(Sold $order): Sold
    {
        if ($order->status === 'B') {
            $order->qr = $this->qrTokenService->makeDeliveryToken(
                (int) $order->id,
                (int) $order->user_id,
                $order->courier_id ? (int) $order->courier_id : null,
            );
        }

        return $order;
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRUVI
    // =========================================================================

    private function validatePromocode(string $rawCode, int $userId, float $total): array
    {
        $code = strtoupper(trim($rawCode));
        $promo = DB::table('promocodes')->where('code', $code)->first();

        if (! $promo) {
            return ['error' => 'Promokod topilmadi!'];
        }
        if ($promo->status != 1) {
            return ['error' => 'Promokod faol emas!'];
        }
        if ((int) ($promo->usesLimit ?? 0) > 0 && (int) ($promo->usedCount ?? 0) >= (int) $promo->usesLimit) {
            return ['error' => 'Promokod limiti tugagan!'];
        }
        if ($promo->expires_at && now()->gt($promo->expires_at)) {
            return ['error' => 'Promokod muddati tugagan!'];
        }
        if (! is_null($promo->user_id) && (int) $promo->user_id !== $userId) {
            return ['error' => 'Bu promokod sizga tegishli emas!'];
        }

        $usedCountByUser = PromocodeHistory::where('user_id', $userId)
            ->where('promocode_id', $promo->id)
            ->count();
        $perUserLimit = (int) ($promo->per_user_limit ?? 1);
        if ($perUserLimit > 0 && $usedCountByUser >= $perUserLimit) {
            return ['error' => $perUserLimit === 1
                ? 'Siz bu promokoddan allaqachon foydalangansiz!'
                : "Siz bu promokoddan maksimal {$perUserLimit} marta foydalana olasiz."];
        }

        if ($promo->min_order_amount && $total < $promo->min_order_amount) {
            return ['error' => "Promokod {$promo->min_order_amount} so'mdan yuqori buyurtmalarga amal qiladi."];
        }

        $discount = match ($promo->type) {
            'uzs', 'fixed' => (int) min($promo->amount, $total),
            'percent' => (function () use ($promo, $total) {
                $percentDiscount = (int) round(($promo->amount / 100) * $total);
                $maxDiscount = (int) ($promo->max_discount_amount ?? 0);

                if ($maxDiscount > 0) {
                    return min($percentDiscount, $maxDiscount);
                }

                return $percentDiscount;
            })(),
            default => 0,
        };

        return ['discount' => $discount, 'promo' => $promo];
    }

    // =========================================================================
    //  CHECKOUT INFO
    // =========================================================================

    public function getCartCheckoutInfo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }
        $this->userReputationService->recalculateUser($user);
        $user->refresh();

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (! $location) {
            return $this->err('Asosiy manzilni belgilang!', 400);
        }

        $selectedCartIds = $request->input('selected_cart_ids', []);
        $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
        if (! empty($selectedCartIds) && is_array($selectedCartIds)) {
            $cartQuery->whereIn('id', $selectedCartIds);
        }

        $cartItems = $cartQuery->get();
        if ($cartItems->isEmpty()) {
            return $this->err("Savatcha bo'sh!", 400);
        }

        $totalSum = 0;
        $sellers = [];

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product || ! $product->seller_id) {
                continue;
            }
            $totalSum += $this->effectiveCartItemUnitPrice($item) * $item->count_item;
            $sellers[$product->seller_id] = true;
        }

        $sellerCount = count($sellers);

        $services = $this->resolveDeliveryOffers($location, $sellerCount, $totalSum);

        return response()->json(['status' => 'success', 'data' => [
            'location' => [
                'id' => $location->id,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'fullAddress' => $location->fullAddress,
                'fullName' => trim(($user->name ?? '').' '.($user->lastname ?? '')),
                'phoneNumber' => $user->phone_number,
                'country_code' => $location->country_code,
            ],
            'cart_info' => [
                'items_count' => $cartItems->sum('count_item'),
                'seller_count' => $sellerCount,
                'price_before_promo' => $totalSum,
                'cashback_balance' => (int) ($user->cashback ?? 0),
            ],
            'user_reputation' => [
                'score' => round((float) ($user->reputation_score ?? UserReputationService::BASELINE_SCORE), 2),
                'cash_on_delivery_allowed' => (bool) ($user->cash_on_delivery_allowed ?? true),
                'cod_return_strikes' => (int) ($user->cod_return_strikes ?? 0),
                'cash_on_delivery_block_reason' => ($user->cash_on_delivery_allowed ?? true)
                    ? null
                    : "Avvalgi naqd buyurtma qaytib kelgani uchun hozircha naqd to'lov yopilgan.",
            ],
            'active_certificates' => GiftCertificate::where('recipient_user_id', $user->id)
                ->where('status', GiftCertificate::STATUS_ACTIVE)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get(['id', 'code', 'nominal_uzs', 'expires_at'])
                ->map(fn ($cert) => [
                    'id' => $cert->id,
                    'code' => $cert->code,
                    'nominal_uzs' => $cert->nominal_uzs,
                    'expires_at' => $cert->expires_at?->format('d.m.Y'),
                ]),
            'delivery_services' => $services,
            'split' => $this->splitAvailabilityPayload($user, (int) $totalSum, $cartItems),
        ]]);
    }

    // =========================================================================
    //  SPLIT (NASIYA) ENDPOINTLARI
    // =========================================================================

    /**
     * Checkout sahifasida Nasiya tile'ini ko'rsatish kerakmi — yengil flag.
     */
    private function splitAvailabilityPayload(User $user, int $productTotal, $cartItems = null): array
    {
        $unavailable = ['available' => false];

        if (! Schema::hasTable('split_plans') || ! Schema::hasTable('split_user_profiles')) {
            return $unavailable;
        }

        $splitService = app(\App\Services\SplitProfileService::class);
        $settings = $splitService->settings();

        if (! $settings['enabled'] || ! $settings['public_enabled']) {
            return $unavailable;
        }

        // Savatda taqiqlangan kategoriyali mahsulot bo'lsa Nasiya tile umuman chiqmaydi
        // (rasmiylashtirishda baribir qattiq tekshiruv bor — bu faqat UX).
        if ($cartItems !== null) {
            $contractService = app(\App\Services\SplitContractService::class);

            if ($contractService->hasCategoryRestrictions()) {
                foreach ($cartItems as $item) {
                    $product = $item->product ?? null;
                    if (! $product) {
                        continue;
                    }

                    $type = str_contains(strtolower((string) ($item->product_type ?? '')), 'stationer')
                        ? 'stationery'
                        : 'book';
                    $categoryId = (int) ($product->category_id ?? 0);

                    if (! $contractService->categoryAllowed($type, $categoryId > 0 ? $categoryId : null)) {
                        return $unavailable;
                    }
                }
            }
        }

        $planBounds = \App\Models\SplitPlan::query()
            ->where('enabled', true)
            ->get(['min_order_sum', 'max_order_sum']);

        if ($planBounds->isEmpty()) {
            return $unavailable;
        }

        // Min/max summa faqat tarif darajasida (bo'sh = cheklovsiz).
        $minPlanSum = (int) $planBounds->min(fn ($plan) => (int) ($plan->min_order_sum ?? 1000));
        $maxPlanSum = (int) $planBounds->max(
            fn ($plan) => $plan->max_order_sum !== null ? (int) $plan->max_order_sum : PHP_INT_MAX,
        );

        // Kesh: profil 24 soatdan yangi bo'lsa qayta hisoblamaymiz (checkout tez ochilsin).
        $profile = \App\Models\SplitUserProfile::query()->where('user_id', $user->id)->first();
        if (! $profile || ! $profile->last_refreshed_at || $profile->last_refreshed_at->lt(now()->subDay())) {
            $fresh = $splitService->refreshUser($user, true);
            $eligible = (bool) $fresh['eligible'];
            $availableLimit = (int) $fresh['available_limit'];
        } else {
            $eligible = (bool) $profile->eligible;
            $availableLimit = (int) $profile->available_limit;
        }

        if (! $eligible || $availableLimit < $minPlanSum) {
            return $unavailable;
        }

        $effectiveMax = min($availableLimit, $maxPlanSum);

        return [
            'available' => true,
            'available_limit' => $availableLimit,
            'min_order_sum' => $minPlanSum,
            'max_order_sum' => $effectiveMax,
            'fits' => $productTotal >= $minPlanSum && $productTotal <= $effectiveMax,
        ];
    }

    /**
     * Nasiya bottomsheet uchun tariflar + aniq jadval.
     * amount = mahsulot qismi (promo'dan keyin), delivery/packaging alohida —
     * ular 1-to'lovga foizsiz qo'shiladi.
     */
    public function splitOffers(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $data = $request->validate([
            'amount' => 'required|integer|min:1000',
            'delivery_fee' => 'nullable|integer|min:0',
            'packaging_fee' => 'nullable|integer|min:0',
        ]);

        if (! Schema::hasTable('split_plans')) {
            return $this->err('Nasiya hozircha mavjud emas.', 422);
        }

        $splitService = app(\App\Services\SplitProfileService::class);
        $scheduleService = app(\App\Services\SplitScheduleService::class);
        $settings = $splitService->settings();

        if (! $settings['enabled'] || ! $settings['public_enabled']) {
            return $this->err('Nasiya hozircha mavjud emas.', 422);
        }

        $amount = (int) $data['amount'];
        $upfrontExtra = (int) ($data['delivery_fee'] ?? 0) + (int) ($data['packaging_fee'] ?? 0);

        $profile = $splitService->getFreshProfile($user);

        if (! $profile['eligible']) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'eligible' => false,
                    'reason' => $profile['eligibility_reasons'][0] ?? null,
                    'available_limit' => 0,
                    'plans' => [],
                ],
            ]);
        }

        $withinLimit = $amount <= (int) $profile['available_limit'];

        $plans = \App\Models\SplitPlan::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('months')
            ->get()
            ->filter(function (\App\Models\SplitPlan $plan) use ($amount, $profile) {
                $minSum = $plan->min_order_sum !== null ? (int) $plan->min_order_sum : 1000;
                $maxSum = $plan->max_order_sum !== null ? (int) $plan->max_order_sum : PHP_INT_MAX;

                if ($amount < $minSum || $amount > $maxSum) {
                    return false;
                }

                if ($plan->min_confidence_score !== null
                    && (float) $profile['confidence_score'] < (float) $plan->min_confidence_score) {
                    return false;
                }

                return true;
            })
            ->map(function (\App\Models\SplitPlan $plan) use ($scheduleService, $amount, $upfrontExtra) {
                $schedule = $scheduleService->calculate($plan, $amount, null, $upfrontExtra);

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'months' => (int) $plan->months,
                    'period_unit' => $plan->period_unit,
                    'period_every' => (int) $plan->period_every,
                    'frequency_label' => $plan->frequencyLabel(),
                    'monthly_interest_percent' => (float) $plan->monthly_interest_percent,
                    'total_interest_percent' => $plan->totalInterestPercent(),
                    'installments_count' => (int) $schedule['installments_count'],
                    'interest' => (int) $schedule['interest'],
                    'total' => (int) $schedule['total'],
                    'first_payment' => (int) $schedule['installments'][0]['amount'],
                    'regular_payment' => count($schedule['installments']) > 1
                        ? (int) $schedule['installments'][1]['amount']
                        : (int) $schedule['installments'][0]['amount'],
                    'installments' => array_map(fn (array $row) => [
                        'sequence' => $row['sequence'],
                        'amount' => $row['amount'],
                        'due_date' => substr($row['due_at'], 0, 10),
                        'is_upfront' => $row['is_upfront'],
                    ], $schedule['installments']),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'eligible' => $withinLimit,
                'reason' => $withinLimit ? null : 'Buyurtma summasi nasiya limitingizdan katta.',
                'available_limit' => (int) $profile['available_limit'],
                'upfront_extra' => $upfrontExtra,
                'plans' => $plans,
            ],
        ]);
    }

    /**
     * Order yaratilgandan keyin (CARD_PENDING) Nasiya rasmiylashtirish:
     * 1-installment hold qilinadi, order HELD bo'ladi, topshirilganda shartnoma faollashadi.
     */
    public function payPendingOrderWithSplit(Request $request, int $order_id)
    {
        $request->validate([
            'card_id' => 'required|integer|min:1',
            'plan_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (! $order) {
            return $this->err('Buyurtma topilmadi!', 404);
        }

        $paymentStatus = PaymentStatusCode::fromLegacy($order->payment_status_code ?? $order->paymentStatus);
        if ($paymentStatus !== PaymentStatusCode::CARD_PENDING) {
            return $this->err('Bu buyurtma to\'lovni kutmayapti.', 422);
        }

        // Nasiya keshbek yoki gift sertifikat bilan birga ishlatilmaydi (promokod mumkin).
        // Klient bu holatni UI'da bloklaydi — bu server tomonidagi qattiq himoya.
        if ((int) ($order->cashbackAmount ?? 0) > 0 || filled($order->gift_certificate_id)) {
            return $this->err('Keshbek yoki sertifikat ishlatilgan buyurtmani nasiyaga rasmiylashtirib bo\'lmaydi.', 422);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (! $card) {
            return $this->err('Karta topilmadi!', 404);
        }

        $plan = \App\Models\SplitPlan::query()->where('enabled', true)->find((int) $request->plan_id);
        if (! $plan) {
            return $this->err('Nasiya tarifi topilmadi!', 404);
        }

        try {
            $contract = app(\App\Services\SplitContractService::class)
                ->openContractForOrder($user, $order, $plan, $card);

            $upfront = $contract->installments()->where('is_upfront', true)->first();

            return response()->json([
                'status' => 'success',
                'message' => "Nasiya rasmiylashtirildi. Birinchi to'lov kartada ushlab turildi.",
                'data' => [
                    'contract_id' => $contract->id,
                    'total' => (int) $contract->total_amount,
                    'first_payment' => (int) ($upfront?->amount ?? 0),
                    'installments_count' => (int) $contract->installments_count,
                    'payment_status' => PaymentStatusCode::HELD->value,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->err($e->getMessage(), 422);
        }
    }

    /**
     * Mahsulot sahifasi uchun PUBLIC nasiya preview (auth talab qilinmaydi).
     * Faqat hisob-kitob: tariflar va jadval — eligibility/limit tekshirilmaydi,
     * rasmiylashtirish baribir checkoutda to'liq tekshiruvdan o'tadi.
     */
    public function splitPreview(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1000',
            'product_type' => 'nullable|in:book,stationery',
            'product_id' => 'nullable|integer|min:1',
        ]);

        $disabled = ['status' => 'success', 'data' => ['enabled' => false, 'plans' => []]];

        if (! Schema::hasTable('split_plans')) {
            return response()->json($disabled);
        }

        $splitService = app(\App\Services\SplitProfileService::class);
        $settings = $splitService->settings();

        if (! $settings['enabled'] || ! $settings['public_enabled']) {
            return response()->json($disabled);
        }

        // Mahsulot kategoriyasi taqiqlangan bo'lsa preview umuman ko'rinmaydi.
        if (filled($data['product_type'] ?? null) && filled($data['product_id'] ?? null)) {
            $productType = (string) $data['product_type'];
            $categoryId = DB::table($productType === 'stationery' ? 'stationeries' : 'books')
                ->where('id', (int) $data['product_id'])
                ->value('category_id');

            $categoryOk = app(\App\Services\SplitContractService::class)->categoryAllowed(
                $productType,
                $categoryId !== null ? (int) $categoryId : null,
            );

            if (! $categoryOk) {
                return response()->json($disabled);
            }
        }

        $amount = (int) $data['amount'];
        $scheduleService = app(\App\Services\SplitScheduleService::class);

        $plans = \App\Models\SplitPlan::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->orderBy('months')
            ->get()
            ->filter(function (\App\Models\SplitPlan $plan) use ($amount) {
                $minSum = $plan->min_order_sum !== null ? (int) $plan->min_order_sum : 1000;
                $maxSum = $plan->max_order_sum !== null ? (int) $plan->max_order_sum : PHP_INT_MAX;

                return $amount >= $minSum && $amount <= $maxSum;
            })
            ->map(function (\App\Models\SplitPlan $plan) use ($scheduleService, $amount) {
                $schedule = $scheduleService->calculate($plan, $amount);

                return [
                    'id' => $plan->id,
                    'months' => (int) $plan->months,
                    'period_unit' => $plan->period_unit,
                    'period_every' => (int) $plan->period_every,
                    'monthly_interest_percent' => (float) $plan->monthly_interest_percent,
                    'total_interest_percent' => $plan->totalInterestPercent(),
                    'installments_count' => (int) $schedule['installments_count'],
                    'interest' => (int) $schedule['interest'],
                    'total' => (int) $schedule['total'],
                    'first_payment' => (int) $schedule['installments'][0]['amount'],
                    'regular_payment' => count($schedule['installments']) > 1
                        ? (int) $schedule['installments'][1]['amount']
                        : (int) $schedule['installments'][0]['amount'],
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => $plans->isNotEmpty(),
                'plans' => $plans,
            ],
        ]);
    }

    /**
     * Profil banneri uchun yengil endpoint: nasiya limiti.
     * Og'ir refresh qilinmaydi — keshlangan profil o'qiladi.
     */
    public function splitLimit()
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $disabled = ['status' => 'success', 'data' => ['enabled' => false, 'eligible' => false, 'available_limit' => 0]];

        if (! Schema::hasTable('split_user_profiles')) {
            return response()->json($disabled);
        }

        $splitService = app(\App\Services\SplitProfileService::class);
        $settings = $splitService->settings();
        if (! $settings['enabled'] || ! $settings['public_enabled']) {
            return response()->json($disabled);
        }

        $profile = \App\Models\SplitUserProfile::query()->where('user_id', $user->id)->first();

        // Profil hali hisoblanmagan yoki eskirgan bo'lsa — shu yerda hisoblab olamiz
        // (aks holda cron ishlamaguncha user limitini ko'rmaydi).
        if (! $profile || ! $profile->last_refreshed_at || $profile->last_refreshed_at->lt(now()->subDay())) {
            try {
                $fresh = $splitService->refreshUser($user, true);

                return response()->json([
                    'status' => 'success',
                    'data' => $this->splitLimitData(
                        eligible: (bool) $fresh['eligible'],
                        reasons: array_values((array) ($fresh['eligibility_reasons'] ?? [])),
                        availableLimit: (int) $fresh['available_limit'],
                        computedLimit: (int) $fresh['computed_limit'],
                        activeContractCount: (int) ($fresh['active_contract_count'] ?? 0),
                    ),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Split] Limit refresh failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->splitLimitData(
                eligible: (bool) ($profile?->eligible ?? false),
                reasons: array_values((array) ($profile?->eligibility_reasons ?? [])),
                availableLimit: (int) ($profile?->available_limit ?? 0),
                computedLimit: (int) ($profile?->computed_limit ?? 0),
                activeContractCount: (int) ($profile?->active_contract_count ?? 0),
            ),
        ]);
    }

    /**
     * split-limit javob shakli. "blocked" = modul yoqilgan, lekin user hozir
     * nasiyaga yaroqsiz VA uning ochiq (pending/active/overdue) shartnomasi bor —
     * ya'ni limiti bo'lgan, biroq masalan muddati o'tgan to'lov sabab vaqtincha
     * bloklangan. Bunda ilova profil bannerida summa o'rniga "Limit bloklangan"
     * ko'rsatadi; user MySplits'ga kirib aniq sababni ko'radi.
     */
    private function splitLimitData(
        bool $eligible,
        array $reasons,
        int $availableLimit,
        int $computedLimit,
        int $activeContractCount,
    ): array {
        $blocked = ! $eligible && $activeContractCount > 0;

        return [
            'enabled' => true,
            'eligible' => $eligible,
            'blocked' => $blocked,
            'blocked_reason' => $blocked ? ($reasons[0] ?? null) : null,
            'available_limit' => $availableLimit,
            'computed_limit' => $computedLimit,
        ];
    }

    /**
     * "Mening nasiyalarim" — userning split shartnomalari, grafigi va
     * "hozir to'lasangiz" kotirovkasi bilan.
     */
    public function mySplitContracts()
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        if (! Schema::hasTable('split_contracts')) {
            return response()->json(['status' => 'success', 'data' => ['contracts' => []]]);
        }

        $contractService = app(\App\Services\SplitContractService::class);

        $contracts = \App\Models\SplitContract::query()
            ->with(['installments.transaction', 'plan:id,name'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (\App\Models\SplitContract $contract) use ($contractService) {
                $isOpen = in_array($contract->status, ['active', 'overdue'], true);
                $serviceFees = max(0, (int) data_get(
                    $contract->meta,
                    'service_fees',
                    (int) data_get($contract->meta, 'delivery_fee', 0) + (int) data_get($contract->meta, 'packaging_fee', 0),
                ));
                $hasInterest = (float) $contract->monthly_interest_percent > 0
                    && (int) $contract->interest_amount > 0;
                $principalAmount = max(0, (int) $contract->principal_amount);
                $totalAmount = max(0, (int) $contract->total_amount);
                $interestAmount = max(0, (int) $contract->interest_amount);

                if ($principalAmount <= 0) {
                    $principalAmount = max(0, (int) data_get($contract->snapshot, 'schedule.principal', 0));
                }

                if ($principalAmount <= 0 && $totalAmount > 0) {
                    $principalAmount = max(0, $totalAmount - $interestAmount - $serviceFees);
                }

                $payoffNow = null;
                if ($isOpen) {
                    $payoffNow = $hasInterest
                        ? (int) $contractService->payoffQuote($contract)['payoff']
                        : (int) $contract->remaining_amount;
                }

                $nextInstallment = $contract->installments
                    ->whereIn('status', ['pending', 'overdue'])
                    ->sortBy('sequence')
                    ->first();

                $monthlyAmount = max(0, (int) optional(
                    $contract->installments->firstWhere('is_upfront', false)
                )->amount);

                if ($monthlyAmount <= 0) {
                    $monthlyAmount = max(0, (int) data_get(
                        collect(data_get($contract->snapshot, 'schedule.installments', []))
                            ->first(fn ($row) => ! data_get($row, 'is_upfront', false)),
                        'amount',
                        0
                    ));
                }

                if ($monthlyAmount <= 0) {
                    $monthlyAmount = max(0, (int) optional($contract->installments->first())->amount);
                }

                return [
                    'id' => $contract->id,
                    'contract_number' => 'N-'.str_pad((string) $contract->id, 5, '0', STR_PAD_LEFT),
                    'status' => $contract->status,
                    'plan_name' => $contract->plan?->name,
                    'months' => (int) $contract->months,
                    'order_id' => $contract->order_id,
                    'total' => $totalAmount,
                    'principal' => $principalAmount,
                    'interest_amount' => $interestAmount,
                    'service_fees' => $serviceFees,
                    'monthly_amount' => $monthlyAmount,
                    'paid' => (int) $contract->paid_amount,
                    'remaining' => (int) $contract->remaining_amount,
                    'ends_at' => optional(
                        $contract->installments->pluck('due_at')->filter()->max()
                    )?->format('Y-m-d'),
                    'has_interest' => $hasInterest,
                    'monthly_interest_percent' => (float) $contract->monthly_interest_percent,
                    'payable' => $isOpen,
                    'payoff_now' => $payoffNow,
                    'next_due_date' => optional($nextInstallment?->due_at)->format('Y-m-d'),
                    'next_amount' => $nextInstallment
                        ? max(0, (int) $nextInstallment->amount - (int) $nextInstallment->paid_amount)
                        : null,
                    'starts_at' => optional($contract->starts_at)->format('Y-m-d'),
                    'installments_paid' => $contract->installments->where('status', 'paid')->count(),
                    'installments_count' => (int) $contract->installments_count,
                    'installments' => $contract->installments->map(function ($installment) {
                        $transaction = $installment->transaction;
                        $perform = is_array($transaction?->perform_fiscal_data)
                            ? $transaction->perform_fiscal_data
                            : [];
                        $receiptUrl = $perform['qr_code_url'] ?? null;

                        if ($installment->status === 'paid'
                            && $transaction !== null
                            && filled($transaction->provider_transaction_id)
                            && blank($receiptUrl)
                            && config('services.paylov.ofd.enabled', false)
                            && \Illuminate\Support\Facades\Cache::add(
                                "ofd:split-backfill:{$transaction->id}",
                                1,
                                now()->addHour(),
                            )
                        ) {
                            \App\Jobs\RegisterTransactionFiscalReceiptJob::dispatch((int) $transaction->id);
                        }

                        return [
                            'sequence' => (int) $installment->sequence,
                            'amount' => (int) $installment->amount,
                            'paid_amount' => (int) $installment->paid_amount,
                            'due_date' => optional($installment->due_at)->format('Y-m-d'),
                            'paid_at' => optional($installment->paid_at)->format('Y-m-d'),
                            'status' => $installment->status,
                            'is_upfront' => (bool) $installment->is_upfront,
                            'has_receipt' => filled($receiptUrl),
                            'receipt_url' => $receiptUrl,
                            'receipt_fiscal_sign' => $perform['fiscal_sign'] ?? null,
                            'receipt_date' => $perform['date'] ?? null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => ['contracts' => $contracts]]);
    }

    /**
     * Tanlangan shartnomalarni tanlangan kartadan to'liq yopish.
     * Foizli shartnomada hali kelmagan oylar foizsiz hisoblanadi (erta yopish).
     */
    public function paySplitContracts(Request $request)
    {
        $request->validate([
            'contract_ids' => 'required|array|min:1|max:10',
            'contract_ids.*' => 'integer|min:1',
            'card_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (! $card) {
            return $this->err('Karta topilmadi!', 404);
        }

        $contractService = app(\App\Services\SplitContractService::class);

        $paidContracts = [];
        $failed = [];
        $totalCharged = 0;

        foreach (array_unique($request->input('contract_ids')) as $contractId) {
            $contract = \App\Models\SplitContract::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'overdue'])
                ->find((int) $contractId);

            if (! $contract) {
                $failed[] = ['id' => (int) $contractId, 'error' => 'Shartnoma topilmadi yoki yopib bo\'lmaydi.'];

                continue;
            }

            try {
                $quote = $contractService->settleEarly($contract, $card);
                $paidContracts[] = (int) $contractId;
                $totalCharged += (int) $quote['payoff'];
            } catch (\Throwable $e) {
                $failed[] = ['id' => (int) $contractId, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => $paidContracts !== [] ? 'success' : 'error',
            'message' => $paidContracts !== []
                ? count($paidContracts).' ta nasiya yopildi.'
                : ($failed[0]['error'] ?? 'To\'lov amalga oshmadi.'),
            'data' => [
                'paid_contract_ids' => $paidContracts,
                'failed' => $failed,
                'total_charged' => $totalCharged,
            ],
        ]);
    }

    /**
     * Tanlangan davrlarni (eng yaqin to'lanmagandan $up_to_sequence gacha —
     * faqat ketma-ket prefix) tanlangan kartadan to'laydi.
     */
    public function paySplitInstallments(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|integer|min:1',
            'up_to_sequence' => 'required|integer|min:1',
            'card_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (! $card) {
            return $this->err('Karta topilmadi!', 404);
        }

        $contract = \App\Models\SplitContract::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'overdue'])
            ->find((int) $request->contract_id);

        if (! $contract) {
            return $this->err('Shartnoma topilmadi yoki to\'lab bo\'lmaydi.', 404);
        }

        try {
            $result = app(\App\Services\SplitContractService::class)
                ->payInstallmentsUpTo($contract, (int) $request->up_to_sequence, $card);
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['paid_installments'].' ta to\'lov amalga oshirildi.',
            'data' => [
                'paid_installments' => $result['paid_installments'],
                'charged' => $result['charged'],
                'error' => $result['error'],
            ],
        ]);
    }

    // =========================================================================
    //  PROMOKOD TEKSHIRISH ENDPOINT
    // =========================================================================

    public function checkPromo(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $code = trim($request->query('code', ''));
        if (! $code) {
            return $this->err('Promokod kiritilmadi!', 400);
        }

        $selectedCartIds = $request->input('selected_cart_ids', []);
        $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
        if (! empty($selectedCartIds) && is_array($selectedCartIds)) {
            $cartQuery->whereIn('id', $selectedCartIds);
        }

        $cartItems = $cartQuery->get();
        if ($cartItems->isEmpty()) {
            return $this->err("Savatcha bo'sh!", 400);
        }

        $total = $cartItems->sum(fn ($i) => $this->effectiveCartItemUnitPrice($i) * $i->count_item);
        $res = $this->validatePromocode($code, $user->id, $total);

        if (isset($res['error'])) {
            return $this->err($res['error']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Promokod yaroqli',
            'discount' => $res['discount'],
            'final_price' => max(0, $total - $res['discount']),
        ]);
    }

    // =========================================================================
    //  BUYURTMA YARATISH
    // =========================================================================

    public function buy_book(Request $request)
    {
        $request->validate([
            'paymentStatus' => 'required|boolean',
            'buyerWish' => 'nullable|string|max:300',
            'promocode' => 'nullable|string|max:50',
            'deliveryservice_id' => 'required|integer|exists:delivery_services,id',
            'withCashback' => 'nullable|boolean',
            'gift_certificate_id' => 'nullable|integer|exists:gift_certificates,id',
            'gift_id' => 'nullable|integer|exists:gifts,id',
            'selected_cart_ids' => 'nullable|array',
            'selected_cart_ids.*' => 'integer',
            'is_gift_to_other' => 'nullable|boolean',
            'with_packaging' => 'nullable|boolean',
            'recipient_phone' => 'nullable|string|max:20',
            'recipient_name' => 'nullable|string|max:150',
            'recipient_region' => 'nullable|string|max:150',
            'recipient_address' => 'nullable|string|max:500',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        // ── PRE-FLIGHT TEKSHIRUV (transaction'dan oldin) ──────────────────
        // Tranzaksiya ichida 500 olishni oldini olish uchun kritik shartlarni
        // shu yerda toza 400 javobi bilan qaytaramiz.
        if (empty($user->mainAddressID)) {
            return $this->err('Yetkazib berish manzilini tanlang.', 400);
        }
        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (! $location) {
            return $this->err('Yetkazib berish manzili topilmadi.', 400);
        }
        $deliveryService = DeliveryService::find($request->deliveryservice_id);
        if (! $deliveryService) {
            return $this->err('Yetkazib berish xizmati topilmadi.', 400);
        }

        if ((int) $request->paymentStatus === 0) {
            $this->userReputationService->recalculateUser($user);
            $user->refresh();

            if (! $user->canUseCashOnDelivery()) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'cash_on_delivery_blocked',
                    'message' => "Avvalgi naqd buyurtma qaytib kelgani uchun hozircha naqd to'lovdan foydalana olmaysiz.",
                    'data' => [
                        'reputation_score' => round((float) ($user->reputation_score ?? UserReputationService::BASELINE_SCORE), 2),
                        'cod_return_strikes' => (int) ($user->cod_return_strikes ?? 0),
                    ],
                ], 422);
            }
        }

        $this->trace('start', ['user_id' => $user->id, 'cart_ids' => $request->input('selected_cart_ids', [])]);

        $collectionCheckoutMeta = $this->collectionCheckoutMeta($request);
        $sourceCollectionId = max(0, (int) ($collectionCheckoutMeta['source_collection_id'] ?? 0));
        $collectionDiscountAmount = max(0, (int) ($collectionCheckoutMeta['collection_discount_amount'] ?? 0));
        $supportsCollectionDiscountAmount = Schema::hasColumn('solds', 'collectionDiscountAmount');
        $supportsSourceCollectionId = Schema::hasColumn('solds', 'source_collection_id');

        DB::beginTransaction();
        try {
            // ── Cart itemlarni olish ──────────────────────────────
            $selectedCartIds = $request->input('selected_cart_ids', []);
            $cartQuery = MyCart::where('user_id', $user->id)->with(['product', 'variant']);
            if (! empty($selectedCartIds)) {
                $cartQuery->whereIn('id', $selectedCartIds);
            }
            $cartItems = $cartQuery->get();

            if ($cartItems->isEmpty()) {
                DB::rollBack();

                return $this->err("Savatcha bo'sh!", 400);
            }
            $this->trace('cart_loaded', ['count' => $cartItems->count()]);

            // ── Mahsulotlarni tayyorlash ──────────────────────────
            $groupedBySeller = [];
            $allItems = [];
            $uniqueSellerIds = [];
            $totalSum = 0;
            $productsToUpdate = [];

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                if (! $product || ! $product->seller_id) {
                    continue;
                }
                if (! Seller::where('id', $product->seller_id)->where('status', 'approved')->exists()) {
                    continue;
                }

                $qty = $cartItem->count_item;
                $price = $this->effectiveCartItemUnitPrice($cartItem);
                $sellerFacingPrice = $this->sellerFacingCartItemUnitPrice($cartItem);
                $stock = $this->availableStock($cartItem);

                if ($qty > $stock) {
                    DB::rollBack();

                    return $this->err("{$product->name} uchun yetarli zaxira yo'q! Mavjud: {$stock}", 400);
                }

                $sellerId = $product->seller_id;
                $groupedBySeller[$sellerId][] = $cartItem;
                $uniqueSellerIds[$sellerId] = true;
                $totalSum += $price * $qty;

                $image = $variant?->image_path ?? $this->pickFirstImage($product->images);
                $item = [
                    'name' => $product->name,
                    'item_price' => $price,
                    'item_id' => $product->id,
                    'count_item' => $qty,
                    'seller_id' => $sellerId,
                    'type' => $cartItem->product_type,
                    'cover' => $image,
                ];

                if ((int) round($sellerFacingPrice) !== (int) round($price)) {
                    $item['seller_item_price'] = (int) round($sellerFacingPrice);
                }

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

                $allItems[] = $item;
                $productsToUpdate[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $qty,
                    'revenue' => $sellerFacingPrice * $qty,
                    'user_id' => $user->id,
                    'type' => $cartItem->product_type,
                ];
            }

            if (empty($allItems)) {
                DB::rollBack();

                return $this->err('Savatchada mahsulot topilmadi!', 400);
            }
            $this->trace('items_prepared', [
                'unique_sellers' => count($uniqueSellerIds),
                'total_items' => count($allItems),
                'total_sum' => $totalSum,
            ]);

            $priceBeforePromo = $totalSum;
            $sellerCount = count($uniqueSellerIds);
            $selectedDeliveryOffer = $this->deliveryZoneResolverService->resolveSelectedOffer(
                $location,
                $sellerCount,
                $priceBeforePromo,
                (int) $deliveryService->id,
            );

            if (! $selectedDeliveryOffer) {
                DB::rollBack();

                return $this->err('Tanlangan manzil uchun bu yetkazish xizmati mavjud emas.', 422);
            }

            if ((int) $request->paymentStatus === 0 && ! ($selectedDeliveryOffer['cod_allowed'] ?? true)) {
                DB::rollBack();

                return $this->err("Bu hudud uchun naqd to'lov o'chirilgan. Iltimos, karta orqali to'lang.", 422);
            }

            // ── Promokod ──────────────────────────────────────────
            $discountAmount = 0;
            $appliedPromo = null;
            $appliedPromoId = null;

            if ($request->filled('promocode')) {
                $res = $this->validatePromocode($request->promocode, $user->id, $priceBeforePromo);
                if (isset($res['error'])) {
                    DB::rollBack();

                    return $this->err($res['error']);
                }
                $discountAmount = $res['discount'];
                $appliedPromo = $res['promo']->code;
                $appliedPromoId = $res['promo']->id;
                $totalSum = max(0, $priceBeforePromo - $discountAmount);
            }

            // ── Yetkazish narxi ───────────────────────────────────
            $deliveryPrice = (int) ($selectedDeliveryOffer['calculated_price'] ?? 0);

            // ── Cashback ──────────────────────────────────────────
            $amountBeforeCashback = $totalSum + $deliveryPrice;
            $useCashback = $request->boolean('withCashback');
            $cashbackBalance = (float) ($user->cashback ?? 0);
            $cashbackUsed = 0;
            $finalPrice = $amountBeforeCashback;

            if ($useCashback && $cashbackBalance > 0 && $request->paymentStatus == 1) {
                $productPart = max(0, $amountBeforeCashback - $deliveryPrice);
                $cashbackUsed = (int) min((int) $cashbackBalance, $productPart);

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
            $certDiscount = 0;
            $appliedCertId = null;
            $appliedCert = null;

            if ($request->filled('gift_certificate_id') && $request->paymentStatus == 1) {
                $cert = GiftCertificate::where('id', $request->gift_certificate_id)
                    ->where('status', GiftCertificate::STATUS_ACTIVE)
                    ->where('recipient_user_id', $user->id)
                    ->first();

                if (! $cert) {
                    DB::rollBack();

                    return $this->err('Gift sertifikat topilmadi yoki sizga tegishli emas!', 400);
                }

                if ($cert->is_expired) {
                    DB::rollBack();

                    return $this->err('Gift sertifikat muddati tugagan!', 400);
                }

                $productRemainder = max(0, $finalPrice - $deliveryPrice);
                $certDiscount = (int) min($cert->nominal_uzs, $productRemainder);
                $finalPrice = max(0, $finalPrice - $certDiscount);
                $appliedCertId = $cert->id;
                $appliedCert = $cert;
            }

            // ── Gift ──────────────────────────────────────────────
            $requestedGiftId = $request->filled('gift_id')
                ? (int) $request->input('gift_id')
                : null;
            $giftId = $requestedGiftId ?? self::DEFAULT_PLATFORM_GIFT_ID;
            $gift = $this->findAvailableGiftForCheckout($giftId);

            // User sovg'a tanlamasa yoki tanlangan sovg'a ayni paytda mavjud
            // bo'lmasa, Kitobchining standart sovg'asiga qaytamiz.
            if (! $gift && $giftId !== self::DEFAULT_PLATFORM_GIFT_ID) {
                $giftId = self::DEFAULT_PLATFORM_GIFT_ID;
                $gift = $this->findAvailableGiftForCheckout($giftId);
            }

            if ($gift) {
                $isDefaultPlatformGift = (int) $gift->id === self::DEFAULT_PLATFORM_GIFT_ID
                    && (int) $gift->seller_id === self::PLATFORM_SELLER_ID;
                $selectedGiftIsEligible = true;

                if (! $isDefaultPlatformGift && (int) $gift->seller_id !== self::PLATFORM_SELLER_ID) {
                    $sellerSumInCart = collect($groupedBySeller[$gift->seller_id] ?? [])
                        ->sum(fn ($item) => $this->effectiveCartItemUnitPrice($item) * $item->count_item);

                    if (
                        ! isset($groupedBySeller[$gift->seller_id]) ||
                        $sellerSumInCart < $gift->priceFrom ||
                        $sellerSumInCart > $gift->priceTo
                    ) {
                        $selectedGiftIsEligible = false;
                    }
                } elseif (! $isDefaultPlatformGift) {
                    if ($priceBeforePromo < $gift->priceFrom || $priceBeforePromo > $gift->priceTo) {
                        $selectedGiftIsEligible = false;
                    }
                }

                if (! $selectedGiftIsEligible) {
                    $giftId = self::DEFAULT_PLATFORM_GIFT_ID;
                    $gift = $this->findAvailableGiftForCheckout($giftId);
                }
            }

            if (! $gift) {
                $giftId = null;
            }

            if ($gift) {
                $allItems[] = [
                    'name' => $gift->name,
                    'item_price' => 0,
                    'item_id' => $gift->id,
                    'count_item' => 1,
                    'seller_id' => $gift->seller_id,
                    'type' => 'gift',
                    'cover' => $this->pickFirstImage($gift->images),
                ];

                $productsToUpdate[] = [
                    'product' => $gift,
                    'variant' => null,
                    'quantity' => 1,
                    'revenue' => 0,
                    'user_id' => $user->id,
                    'type' => 'gift',
                ];

                $gift->decrement('stock', 1);
                if ($gift->stock < 0) {
                    $gift->stock = 0;
                    $gift->save();
                }
            }

            // ── Packaging narxi ───────────────────────────────────
            // Cashback va sertifikatdan keyin qo'shiladi —
            // chunki packaging chegirmaga kirmaydi
            $withPackaging = $request->boolean('with_packaging');
            $bookItemCount = collect($allItems)->where('type', 'book')->sum('count_item');
            $packagingPrice = 0;

            if ($withPackaging) {
                $packagingPrice = $bookItemCount >= 4 ? 40000 : 25000;
                $finalPrice += $packagingPrice;
            }

            // ── Manzil ────────────────────────────────────────────
            $locationData = [
                'fullName' => trim("{$user->name} {$user->lastname}"),
                'fullAddress' => $location->fullAddress,
                'lat' => $location->lat,
                'lon' => $location->lon,
                'country_code' => $location->country_code,
                'phoneNumber' => $user->phone_number,
            ];

            // ── Asosiy buyurtma yaratish ──────────────────────────
            // Defensiv casts — DB columnlari int/tinyint ga (string emas)
            // mos kelishi kerak. Bo'sh stringlar yoki noto'g'ri tipdan
            // INSERT'da SQLSTATE xatolari kelmasligi uchun aniq cast qilamiz.
            $normalizedDeliveryType = $this->resolveDeliveryType($deliveryService);

            $purchasePayload = [
                'user_id' => (int) $user->id,
                'qr' => Str::random(40),
                'items' => $allItems,
                'address' => [$locationData],
                'deliveryType' => $normalizedDeliveryType,
                'deliveryPrice' => (int) $deliveryPrice,
                'delivery_zone_rule_id' => (int) ($selectedDeliveryOffer['zone_rule_id'] ?? 0) ?: null,
                'delivery_rule_snapshot' => [
                    'service_id' => (int) $deliveryService->id,
                    'service_name' => $deliveryService->name,
                    'service_type' => $deliveryService->type,
                    'zone_rule_id' => (int) ($selectedDeliveryOffer['zone_rule_id'] ?? 0),
                    'zone_name' => $selectedDeliveryOffer['zone_name'] ?? null,
                    'zone_scope' => $selectedDeliveryOffer['zone_scope'] ?? null,
                    'country_code' => $selectedDeliveryOffer['country_code'] ?? $location->country_code,
                    'calculated_price' => (int) $deliveryPrice,
                    'cod_allowed' => (bool) ($selectedDeliveryOffer['cod_allowed'] ?? true),
                    'eta_days' => (int) ($selectedDeliveryOffer['muddat'] ?? 0),
                    'base_delivery_price' => (int) ($selectedDeliveryOffer['base_delivery_price'] ?? $deliveryPrice),
                    'additional_seller_price' => (int) ($selectedDeliveryOffer['additional_seller_price'] ?? 0),
                    'additional_seller_percent' => (float) ($selectedDeliveryOffer['additional_seller_percent'] ?? 0),
                    'seller_count' => (int) ($selectedDeliveryOffer['seller_count'] ?? $sellerCount),
                ],
                'paymentStatus' => $request->paymentStatus
                    ? PaymentStatusCode::CARD_PENDING->legacy()
                    : PaymentStatusCode::CASH_PENDING->legacy(),
                'payment_status_code' => $request->paymentStatus
                    ? PaymentStatusCode::CARD_PENDING->value
                    : PaymentStatusCode::CASH_PENDING->value,
                'amount' => (int) round($finalPrice),
                'status' => OrderStatusCode::PENDING->legacy(),
                'status_code' => OrderStatusCode::PENDING->value,
                'gift' => $giftId !== null ? (int) $giftId : null,
                'buyerWish' => Str::limit(trim(strip_tags($request->input('buyerWish', ''))), 300),
                'promocode' => $appliedPromo,
                'discountAmount' => (int) ($discountAmount + ($supportsCollectionDiscountAmount ? 0 : $collectionDiscountAmount)),
                'withCashback' => (bool) ($useCashback && $cashbackUsed > 0),
                'cashbackAmount' => (int) $cashbackUsed,
                'gift_certificate_id' => $appliedCertId !== null ? (int) $appliedCertId : null,
                'giftCertAmount' => (int) $certDiscount,
                'is_gift_to_other' => (bool) $request->boolean('is_gift_to_other'),
                'with_packaging' => (bool) $withPackaging,
                'packaging_price' => (int) $packagingPrice,
                'recipient_phone' => $request->input('recipient_phone'),
                'recipient_name' => $request->input('recipient_name'),
                'recipient_region' => $request->input('recipient_region'),
                'recipient_address' => $request->input('recipient_address'),
            ];

            if ($supportsCollectionDiscountAmount) {
                $purchasePayload['collectionDiscountAmount'] = $collectionDiscountAmount;
            }

            if ($supportsSourceCollectionId && $sourceCollectionId > 0) {
                $purchasePayload['source_collection_id'] = $sourceCollectionId;
            }

            $purchase = Sold::create($purchasePayload);
            $this->trace('sold_created', ['order_id' => $purchase->id, 'amount' => $finalPrice]);

            $routingDecision = $this->fulfillmentRoutingService->decide(
                buyerLocation: $location,
                deliveryService: $deliveryService,
                selectedDeliveryOffer: $selectedDeliveryOffer,
                sellerCount: $sellerCount,
                withPackaging: (bool) $withPackaging,
                containsBooks: $bookItemCount > 0,
                isCashOnDelivery: (int) $request->paymentStatus === 0,
                cashCollectAmount: (int) round($finalPrice),
            );

            if (
                in_array($routingDecision['mode'], [FulfillmentMode::HUB_BASED, FulfillmentMode::POSTAL_ONLY_VIA_HUB], true)
                && ! $routingDecision['hub']
            ) {
                DB::rollBack();

                return $this->err('Bu buyurtma uchun mos hub topilmadi. Iltimos, logistika sozlamalarini tekshiring.', 422);
            }

            $fulfillment = $this->fulfillmentRoutingService->createForOrder(
                order: $purchase,
                routingDecision: $routingDecision,
                deliveryService: $deliveryService,
                deliveryZoneRuleId: (int) ($selectedDeliveryOffer['zone_rule_id'] ?? 0) ?: null,
            );
            $this->trace('fulfillment_created', [
                'order_id' => $purchase->id,
                'fulfillment_id' => $fulfillment->id,
                'mode' => $routingDecision['mode']->value,
                'hub_id' => $routingDecision['hub']?->id,
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
            // Har sotuvchi uchun bitta SellerOrder + nechta SellerOrderItem.
            // Defensiv casts: amount/quantity/price MySQL'da int — float
            // berilsa strict mode'da xato ehtimoli bor.
            $sellerMainLocations = [];
            foreach ($groupedBySeller as $sellerId => $items) {
                $sellerAmount = 0;
                $sellerLocation = DB::table('seller_locations')
                    ->where('seller_id', $sellerId)
                    ->where('is_deleted', false)
                    ->orderByDesc('is_main')
                    ->orderBy('id')
                    ->first(['id', 'fullAddress', 'lat', 'lon', 'is_main']);

                if (! $sellerLocation) {
                    DB::rollBack();
                    Log::warning('Purchase blocked: seller has no active location', [
                        'seller_id' => (int) $sellerId,
                        'user_id' => (int) $user->id,
                    ]);

                    return $this->err(
                        "Sotuvchi filiali topilmadi. Iltimos, do'kon joylashuvini tekshiring.",
                        422
                    );
                }

                $sellerMainLocations[(int) $sellerId] = $sellerLocation;
                $sellerOrderAddress = array_merge($locationData, [
                    'location_id' => (int) $sellerLocation->id,
                    'branch_address' => $sellerLocation->fullAddress,
                    'branch_lat' => $sellerLocation->lat,
                    'branch_lon' => $sellerLocation->lon,
                    'branch_is_main' => true,
                ]);

                $sellerOrder = SellerOrder::create([
                    'seller_id' => (int) $sellerId,
                    'order_id' => (int) $purchase->id,
                    'client_id' => (int) $user->id,
                    'status' => $request->paymentStatus == 1
                        ? SellerOrderStatusCode::PAYMENT_PENDING->legacy()
                        : SellerOrderStatusCode::NEW->legacy(),
                    'status_code' => $request->paymentStatus == 1
                        ? SellerOrderStatusCode::PAYMENT_PENDING->value
                        : SellerOrderStatusCode::NEW->value,
                    'delivery_type' => $normalizedDeliveryType,
                    'address' => [$sellerOrderAddress],
                ]);

                foreach ($items as $item) {
                    $product = $item->product;
                    $qty = (int) $item->count_item;
                    $price = $this->sellerFacingCartItemUnitPrice($item);
                    $sellerAmount += $price * $qty;

                    SellerOrderItem::create([
                        'seller_id' => (int) $sellerId,
                        'order_id' => (int) $sellerOrder->id,
                        'product_id' => (int) $product->id,
                        'type' => (string) $item->product_type,
                        'quantity' => $qty,
                        'price' => (int) round($price),
                        'variant_id' => $item->variant_id ? (int) $item->variant_id : null,
                    ]);
                }

                if (
                    isset($gift)
                    && $gift
                    && (int) $gift->seller_id !== self::PLATFORM_SELLER_ID
                    && (int) $gift->seller_id === (int) $sellerId
                ) {
                    SellerOrderItem::create([
                        'seller_id' => (int) $sellerId,
                        'order_id' => (int) $sellerOrder->id,
                        'product_id' => (int) $gift->id,
                        'type' => 'gift',
                        'quantity' => 1,
                        'price' => 0,
                        'variant_id' => null,
                    ]);
                }

                $sellerOrder->update(['amount' => (int) round($sellerAmount)]);
            }
            $this->trace('seller_orders_created', ['count' => count($groupedBySeller)]);
            $this->orderFinancialSnapshotService->ensureSnapshotsForOrder($purchase);

            // ── Courier order ─────────────────────────────────────
            $courierOrder = CourierOrder::create([
                'courier_id' => null,
                'order_id' => (int) $purchase->id,
                'user_id' => (int) $user->id,
                'amount' => (int) round($totalSum),
                'status' => $request->paymentStatus == 1
                    ? CourierOrderStatusCode::PAYMENT_PENDING->legacy()
                    : CourierOrderStatusCode::PENDING->legacy(),
                'status_code' => $request->paymentStatus == 1
                    ? CourierOrderStatusCode::PAYMENT_PENDING->value
                    : CourierOrderStatusCode::PENDING->value,
                'courierPrice' => (int) $deliveryPrice,
                'courierBonus' => 0,
            ]);

            foreach ($allItems as $itm) {
                $itm_type = (string) ($itm['type'] ?? '');
                $itm_sid = (int) ($itm['seller_id'] ?? 1);

                if ($itm_type === 'gift' && $itm_sid === self::PLATFORM_SELLER_ID) {
                    continue;
                }
                if ($itm_type === 'gift' && ! isset($groupedBySeller[$itm_sid])) {
                    continue;
                }

                $sellerLocation = $sellerMainLocations[$itm_sid] ?? null;

                if (! $sellerLocation) {
                    throw new \RuntimeException("Seller location not found for seller_id: {$itm_sid}");
                }

                CourierOrderItem::create([
                    'seller_id' => $itm_sid,
                    'seller_location_id' => (int) $sellerLocation->id,
                    'order_id' => (int) $purchase->id,
                    'type' => $itm_type,
                    'product_id' => isset($itm['item_id']) ? (int) $itm['item_id'] : null,
                    'quantity' => (int) ($itm['count_item'] ?? 1),
                    'price' => (int) round($itm['item_price'] ?? 0),
                    'variant_id' => isset($itm['variant_id']) ? (int) $itm['variant_id'] : null,
                ]);
            }
            $this->trace('courier_order_created', ['order_id' => $courierOrder->id]);

            $this->courierTaskOrchestratorService->ensureTasksForOrder($purchase);
            $this->trace('courier_tasks_created', ['order_id' => $purchase->id]);

            // ── Statistika va stock yangilash ─────────────────────
            foreach ($productsToUpdate as $data) {
                if (($data['type'] ?? null) === 'gift') {
                    $data['product']->increment('totalSales', 1);
                    $data['product']->increment('totalSalesWeek', 1);
                    $data['product']->save();

                    continue;
                }
                $this->orderService->decrementStock($data);
                $this->orderService->incrementProductStats($data, $purchase->id);
            }
            $this->trace('stock_updated');

            // ── Promokod tarixi ───────────────────────────────────
            if ($appliedPromoId) {
                PromocodeHistory::create([
                    'user_id' => (int) $user->id,
                    'promocode_id' => (int) $appliedPromoId,
                ]);
                DB::table('promocodes')->where('id', $appliedPromoId)->increment('usedCount');
            }

            // ── Gift Sertifikat ishlatish ──────────────────────────
            if ($appliedCert && $certDiscount > 0) {
                $appliedCert->useInPurchase((int) $certDiscount);
            }

            // ── Naqd to'lov — seller/courier orderlarni activate qilish ─
            // handleOrderPaid chaqirilmaydi: paymentStatus=0 qolsin,
            // mijoz hali to'lamagan (naqd yetkazilganda to'laydi).
            // Bekor qilish imkoni saqlanib qoladi.
            if ($request->paymentStatus == 0) {
                SellerOrder::where('order_id', $purchase->id)->update([
                    'status' => SellerOrderStatusCode::NEW->legacy(),
                    'status_code' => SellerOrderStatusCode::NEW->value,
                ]);
                CourierOrder::where('order_id', $purchase->id)->update([
                    'status' => CourierOrderStatusCode::PENDING->legacy(),
                    'status_code' => CourierOrderStatusCode::PENDING->value,
                ]);
            }

            // ── Faqat tanlangan cart itemlarni o'chirish ──────────
            if (! empty($selectedCartIds)) {
                MyCart::where('user_id', $user->id)->whereIn('id', $selectedCartIds)->delete();
            } else {
                MyCart::where('user_id', $user->id)->delete();
            }

            DB::commit();

            $freshPurchase = Sold::find($purchase->id);
            $freshCourierOrder = CourierOrder::where('order_id', $purchase->id)->first();
            $this->orderRealtimeService->broadcastSoldCreated(
                $freshPurchase,
                array_keys($groupedBySeller),
                $freshCourierOrder,
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Buyurtma muvaffaqiyatli yaratildi',
                'order_id' => $purchase->id,
                'payment_status' => $purchase->fresh()->paymentStatus,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->all(),
            ]);

            // Productiondan tashqari muhitda mijozga aniq xato qaytariladi —
            // shunda Flutter konsolidan to'g'ridan-to'g'ri sabab ko'rinadi.
            // Productionda umumiy xabar saqlanadi.
            $payload = ['status' => 'error', 'message' => 'Buyurtma yaratishda xatolik yuz berdi!'];
            if (! app()->environment('production')) {
                $payload['debug'] = [
                    'msg' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json($payload, 500);
        }
    }

    // =========================================================================
    //  IN-STORE BUYURTMA — mijoz do'kondagi QR'ni skanerlab, mahsulotlarni
    //  o'zi tanlaydi. To'lov keyin ilova ichidagi saved-card oqimida tugallanadi.
    //
    //  POST /api/v1/kitobchi/in-store/buy
    //  Body:
    //   - seller_id (required, integer): scan qilingan do'kon
    //   - items (required, array): [{ product_id, type, quantity, variant_id? }]
    //   - promocode (nullable, string)
    //
    //  Effekt:
    //   - Sold qator (deliveryType=pickup, deliveryPrice=0, paymentStatus=1)
    //   - SellerOrder qator (faqat 1 ta — bitta sotuvchi)
    //   - CourierOrder yaratilmaydi (mijoz do'konda olib ketadi)
    //   - Stock decrement va statistika yangilanishi
    //   - Javobda order_id
    // =========================================================================
    public function inStoreBuy(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|integer|exists:sellers,id',
            'location_id' => 'nullable|integer|exists:seller_locations,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.type' => 'required|string|in:book,stationery',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|integer|exists:stationery_variants,id',
            'promocode' => 'nullable|string|max:50',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $sellerId = (int) $request->input('seller_id');
        $locationId = $request->filled('location_id') ? (int) $request->input('location_id') : null;
        $sellerOk = Seller::where('id', $sellerId)
            ->where('status', 'approved')
            ->where('is_hidden', 0)
            ->exists();
        if (! $sellerOk) {
            return $this->err("Do'kon topilmadi yoki o'chirilgan.", 404);
        }

        DB::beginTransaction();
        try {
            $allItems = [];
            $totalSum = 0;
            $productsToUpdate = [];

            foreach ($request->input('items') as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $type = (string) ($row['type'] ?? 'book');
                $quantity = max(1, (int) ($row['quantity'] ?? 0));
                $variantId = isset($row['variant_id']) ? (int) $row['variant_id'] : null;

                if ($type === 'book') {
                    $book = Books::where('id', $productId)
                        ->where('seller_id', $sellerId)
                        ->where('is_approved', 1)
                        ->where('is_hidden', 0)
                        ->first();

                    if (! $book) {
                        DB::rollBack();

                        return $this->err("Bu do'konda mahsulot topilmadi (id={$productId}).", 404);
                    }

                    $stock = (int) ($book->count ?? 0);
                    if ($quantity > $stock) {
                        DB::rollBack();

                        return $this->err("{$book->name} uchun yetarli zaxira yo'q! Mavjud: {$stock}", 400);
                    }

                    $price = $this->effectivePrice($book);
                    $totalSum += $price * $quantity;

                    $allItems[] = [
                        'name' => $book->name,
                        'item_price' => $price,
                        'item_id' => $book->id,
                        'count_item' => $quantity,
                        'seller_id' => $sellerId,
                        'type' => 'book',
                        'variant_id' => null,
                        'cover' => $book->images[0] ?? null,
                        'author' => $book->author,
                    ];
                    $productsToUpdate[] = [
                        'product' => $book,
                        'variant' => null,
                        'quantity' => $quantity,
                        'revenue' => $price * $quantity,
                        'user_id' => $user->id,
                        'type' => 'book',
                    ];

                    continue;
                }

                $stationery = \App\Models\Stationery::where('id', $productId)
                    ->where('seller_id', $sellerId)
                    ->where('is_approved', 1)
                    ->where('is_hidden', 0)
                    ->with('variants')
                    ->first();

                if (! $stationery) {
                    DB::rollBack();

                    return $this->err("Bu do'konda mahsulot topilmadi (id={$productId}).", 404);
                }

                $variant = null;
                $stock = (int) ($stationery->stock ?? 0);
                $cover = $stationery->images[0] ?? null;

                if ($variantId) {
                    $variant = $stationery->variants->firstWhere('id', $variantId, null);
                    if (! $variant) {
                        DB::rollBack();

                        return $this->err('Kanselyariya varianti topilmadi.', 404);
                    }
                    $stock = (int) ($variant->stock ?? 0);
                    $cover = $variant->image_path ?: $cover;
                } elseif ($stationery->variants->isNotEmpty()) {
                    DB::rollBack();

                    return $this->err('Variantli kanselyariya uchun variant tanlanishi kerak.', 422);
                }

                if ($quantity > $stock) {
                    DB::rollBack();

                    return $this->err("{$stationery->name} uchun yetarli zaxira yo'q! Mavjud: {$stock}", 400);
                }

                $price = $this->effectivePrice($stationery);
                $totalSum += $price * $quantity;

                $allItems[] = [
                    'name' => $stationery->name,
                    'item_price' => $price,
                    'item_id' => $stationery->id,
                    'count_item' => $quantity,
                    'seller_id' => $sellerId,
                    'type' => 'stationery',
                    'variant_id' => $variant?->id,
                    'cover' => $cover,
                    'author' => $stationery->material,
                ];
                $productsToUpdate[] = [
                    'product' => $stationery,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'revenue' => $price * $quantity,
                    'user_id' => $user->id,
                    'type' => 'stationery',
                ];
            }

            if (empty($allItems)) {
                DB::rollBack();

                return $this->err('Mahsulot tanlanmagan!', 400);
            }

            // ── Promokod ──────────────────────────────────────────
            $priceBeforePromo = $totalSum;
            $discountAmount = 0;
            $appliedPromo = null;
            $appliedPromoId = null;

            if ($request->filled('promocode')) {
                $res = $this->validatePromocode($request->promocode, $user->id, $priceBeforePromo);
                if (isset($res['error'])) {
                    DB::rollBack();

                    return $this->err($res['error']);
                }
                $discountAmount = $res['discount'];
                $appliedPromo = $res['promo']->code;
                $appliedPromoId = $res['promo']->id;
                $totalSum = max(0, $priceBeforePromo - $discountAmount);
            }

            $finalPrice = $totalSum; // delivery 0, packaging 0

            // ── Asosiy buyurtma ───────────────────────────────────
            // address: do'kon ichida xarid — userning shahriy manzili emas,
            // balki seller_locations'dagi do'kon manzilini saqlaymiz.
            $sellerLocationQuery = DB::table('seller_locations')
                ->where('seller_id', $sellerId)
                ->where('is_deleted', 0);

            if ($locationId) {
                $sellerLocationQuery->where('id', $locationId);
            } else {
                $sellerLocationQuery->orderByDesc('is_main')->orderBy('id');
            }

            $sellerLocation = $sellerLocationQuery->first(['id', 'fullAddress', 'lat', 'lon', 'is_main']);

            if (! $sellerLocation) {
                DB::rollBack();

                return $this->err("Filial topilmadi yoki noto'g'ri tanlangan.", 404);
            }

            $purchase = Sold::create([
                'user_id' => $user->id,
                'qr' => Str::random(40),
                'items' => $allItems,
                'address' => [[
                    'fullName' => trim("{$user->name} {$user->lastname}"),
                    'fullAddress' => $sellerLocation->fullAddress ?? "Do'kon ichida xarid",
                    'lat' => $sellerLocation->lat ?? null,
                    'lon' => $sellerLocation->lon ?? null,
                    'phoneNumber' => $user->phone_number,
                    'location_id' => (int) $sellerLocation->id,
                    'branch_address' => $sellerLocation->fullAddress ?? "Do'kon ichida xarid",
                    'branch_is_main' => (bool) ($sellerLocation->is_main ?? false),
                ]],
                'deliveryType' => 'pickup',
                'is_instore' => true,
                'deliveryPrice' => 0,
                'paymentStatus' => 1,
                'amount' => $finalPrice,
                'promocode' => $appliedPromo,
                'discountAmount' => $discountAmount,
            ]);

            // ── Seller order ─────────────────────────────────────
            $sellerOrder = SellerOrder::create([
                'seller_id' => $sellerId,
                'order_id' => $purchase->id,
                'client_id' => $user->id,
                'status' => 0, // pay_process
                'delivery_type' => 'pickup',
                'address' => [[
                    'fullAddress' => $sellerLocation->fullAddress ?? "Do'kon ichida xarid",
                    'location_id' => (int) $sellerLocation->id,
                    'branch_address' => $sellerLocation->fullAddress ?? "Do'kon ichida xarid",
                    'branch_is_main' => (bool) ($sellerLocation->is_main ?? false),
                ]],
                'amount' => $priceBeforePromo,
            ]);

            foreach ($allItems as $itm) {
                SellerOrderItem::create([
                    'seller_id' => $sellerId,
                    'order_id' => $sellerOrder->id,
                    'product_id' => $itm['item_id'],
                    'type' => $itm['type'],
                    'quantity' => $itm['count_item'],
                    'price' => $itm['item_price'],
                    'variant_id' => $itm['variant_id'] ?? null,
                ]);
            }

            $this->orderFinancialSnapshotService->ensureSnapshotsForOrder($purchase);

            // ── Stock + statistika ───────────────────────────────
            foreach ($productsToUpdate as $data) {
                $this->orderService->decrementStock($data);
                $this->orderService->incrementProductStats($data, $purchase->id);
            }

            // ── Promokod tarixi ──────────────────────────────────
            if ($appliedPromoId) {
                PromocodeHistory::create(['user_id' => $user->id, 'promocode_id' => $appliedPromoId]);
                DB::table('promocodes')->where('id', $appliedPromoId)->increment('usedCount');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'order_id' => $purchase->id,
                'amount' => $finalPrice,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('In-store purchase failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $payload = ['status' => 'error', 'message' => 'Buyurtma yaratishda xatolik yuz berdi!'];
            if (! app()->environment('production')) {
                $payload['debug'] = [
                    'msg' => $e->getMessage(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json($payload, 500);
        }
    }

    // =========================================================================
    //  BUYURTMANI BEKOR QILISH
    // =========================================================================

    public function cancelOrder(Request $request, $orderId)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('id', $orderId)->where('user_id', $user->id)->first();
        if (! $order) {
            return $this->err('Buyurtma topilmadi!', 404);
        }

        $result = $this->orderService->cancelOrder($order, strict: true);
        if (! $result['ok']) {
            return $this->err($result['message'], 400);
        }

        return response()->json(['status' => 'success', 'message' => $result['message']]);
    }

    public function cashbackHistory(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

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
        if (! $user) {
            return $this->err('Foydalanuvchi mavjud emas!', 401);
        }

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (! $location) {
            return $this->err('Asosiy manzil belgilanmagan!', 400);
        }

        return response()->json(['status' => 'success', 'data' => [
            'location' => $location->id,
            'location_lat' => $location->lat,
            'location_lon' => $location->lon,
            'location_fullAddress' => $location->fullAddress,
        ]]);
    }

    public function delivery_service(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Unauthorized', 401);
        }

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (! $location) {
            return $this->err('Asosiy manzil belgilanmagan!', 400);
        }

        $services = $this->resolveDeliveryOffers($location, 1, 0);

        return response()->json(['status' => 'success', 'data' => $services]);
    }

    public function purchaseList(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $request->validate([
            'status' => 'nullable|string|in:A,P,B,C,D,F,progress,pending,packing,in_delivery,delivered,customer_received,cancelled,returned',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'periods_selected' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'grouped' => 'nullable|boolean',
            'periods' => 'nullable|boolean',
            'review_prompts' => 'nullable|boolean',
            'section' => 'nullable|string|in:progress,in_delivery,delivered,cancelled',
        ]);

        if ($request->boolean('periods')) {
            return response()->json([
                'status' => 'success',
                'periods' => $this->availablePurchasePeriods($user),
            ]);
        }

        if ($request->boolean('review_prompts')) {
            return $this->reviewPromptProducts($request, $user);
        }

        if ($request->boolean('grouped') && ! $request->filled('status')) {
            return $this->purchaseListGrouped($request, $user);
        }

        $query = Sold::where('user_id', $user->id);

        if ($request->filled('status')) {
            if ($request->status === 'progress') {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereIn('status_code', ['pending', 'packing'])
                        ->orWhere(function ($fallback) {
                            $fallback->whereNull('status_code')
                                ->whereIn('status', ['A', 'P']);
                        });
                });
            } else {
                $statusCode = OrderStatusCode::fromLegacy($request->status);
                $query->where(function ($statusQuery) use ($statusCode) {
                    $statusQuery->where('status_code', $statusCode->value)
                        ->orWhere(function ($fallback) use ($statusCode) {
                            $fallback->whereNull('status_code')
                                ->where('status', $statusCode->legacy());
                        });
                });
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(function ($order) {
            $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
            $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
            $this->appendOrderStatusMeta($order);

            return $this->applySignedDeliveryQr($order);
        });

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function purchaseListGrouped(Request $request, User $user)
    {
        $perPage = (int) $request->input('per_page', 3);
        $perPage = max(1, min(12, $perPage));

        $section = trim((string) $request->input('section', ''));
        if ($section !== '') {
            $page = max(1, (int) $request->input('page', 1));
            $paginator = $this->buildPurchaseSectionQuery($user, $request, $section)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            $this->transformPurchasePaginator($paginator);

            return response()->json([
                'status' => 'success',
                'section' => $section,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ]);
        }

        $sections = [];
        foreach (['progress', 'in_delivery', 'delivered', 'cancelled'] as $sectionKey) {
            $paginator = $this->buildPurchaseSectionQuery($user, $request, $sectionKey)
                ->latest()
                ->paginate($perPage, ['*'], $sectionKey.'_page', 1);

            $this->transformPurchasePaginator($paginator);

            if (count($paginator->items()) === 0) {
                continue;
            }

            $sections[$sectionKey] = [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];
        }

        $reviewPromptPage = $this->buildReviewPromptProductPage($user, 1, 20);
        $reviewCashback = $this->buildReviewCashbackPayload(
            app(\App\Services\ReviewCashbackService::class)->settings(),
            (int) ($reviewPromptPage['meta']['total'] ?? count($reviewPromptPage['data']))
        );

        return response()->json([
            'status' => 'success',
            'sections' => $sections,
            'review_prompt_items' => $reviewPromptPage['data'],
            'review_prompt_meta' => $reviewPromptPage['meta'],
            'review_cashback' => $reviewCashback,
        ]);
    }

    private function buildPurchaseSectionQuery(User $user, Request $request, string $sectionKey)
    {
        $query = Sold::query()->where('user_id', $user->id);
        $this->applyPurchaseDateFilters($query, $request);

        return match ($sectionKey) {
            'progress' => $query->where(function ($statusQuery) {
                $statusQuery->whereIn('status_code', ['pending', 'packing'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', ['A', 'P']);
                    });
            }),
            'in_delivery' => $query->where(function ($statusQuery) {
                $statusQuery->where('status_code', 'in_delivery')
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->where('status', 'B');
                    });
            }),
            'delivered' => $query->where(function ($statusQuery) {
                $statusQuery->whereIn('status_code', ['delivered', 'customer_received'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', ['C', 'D']);
                    });
            }),
            'cancelled' => $query->where(function ($statusQuery) {
                $statusQuery->whereIn('status_code', ['cancelled', 'returned'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status_code')
                            ->whereIn('status', ['F', 'R']);
                    });
            }),
            default => $query,
        };
    }

    private function applyPurchaseDateFilters($query, Request $request): void
    {
        if ($request->filled('periods_selected')) {
            $pairs = collect(explode(',', (string) $request->periods_selected))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->map(function ($value) {
                    if (! preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
                        return null;
                    }

                    $year = (int) $m[1];
                    $month = (int) $m[2];

                    if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                        return null;
                    }

                    return ['year' => $year, 'month' => $month];
                })
                ->filter()
                ->values();

            if ($pairs->isNotEmpty()) {
                $query->where(function ($periodQuery) use ($pairs) {
                    foreach ($pairs as $pair) {
                        $periodQuery->orWhere(function ($single) use ($pair) {
                            $single->whereYear('created_at', $pair['year'])
                                ->whereMonth('created_at', $pair['month']);
                        });
                    }
                });

                return;
            }
        }

        if ($request->filled('year') && $request->filled('month')) {
            $query->whereYear('created_at', (int) $request->year)
                ->whereMonth('created_at', (int) $request->month);

            return;
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
    }

    private function availablePurchasePeriods(User $user): array
    {
        return Sold::query()
            ->where('user_id', $user->id)
            ->selectRaw('YEAR(created_at) as year_value, MONTH(created_at) as month_value, MAX(created_at) as latest_created_at')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByDesc('year_value')
            ->orderByDesc('month_value')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year_value,
                'month' => (int) $row->month_value,
                'latest_created_at' => $row->latest_created_at,
            ])
            ->values()
            ->all();
    }

    private function transformPurchasePaginator($paginator): void
    {
        $paginator->getCollection()->transform(function ($order) {
            $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
            $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
            $this->appendOrderSellerMeta($order);
            $this->appendOrderStatusMeta($order);

            return $this->applySignedDeliveryQr($order);
        });
    }

    private function reviewPromptProducts(Request $request, User $user)
    {
        $perPage = (int) $request->input('per_page', 8);
        $perPage = max(1, min(20, $perPage));
        $page = max(1, (int) $request->input('page', 1));

        $result = $this->buildReviewPromptProductPage($user, $page, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => $result['data'],
            'meta' => $result['meta'],
            'review_cashback' => $this->buildReviewCashbackPayload(
                app(\App\Services\ReviewCashbackService::class)->settings(),
                (int) ($result['meta']['total'] ?? count($result['data']))
            ),
        ]);
    }

    private function buildReviewPromptProducts(User $user, int $limit = 20): array
    {
        return $this->buildReviewPromptProductPage($user, 1, $limit)['data'];
    }

    private function buildReviewPromptProductPage(User $user, int $page, int $perPage): array
    {
        $unique = [];
        $bookIds = [];
        $stationeryIds = [];

        $ordersQuery = Sold::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where(function ($query) {
                $query->where('payment_status_code', PaymentStatusCode::PAID->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('payment_status_code')
                            ->where('paymentStatus', PaymentStatusCode::PAID->legacy());
                    });
            })
            ->orderByDesc('completed_at')
            ->orderByDesc('id');

        $ordersPage = 1;
        $ordersPerChunk = 100;

        while (true) {
            $orders = (clone $ordersQuery)
                ->forPage($ordersPage, $ordersPerChunk)
                ->get(['id', 'items', 'completed_at']);

            if ($orders->isEmpty()) {
                break;
            }

            foreach ($orders as $order) {
                foreach (collect($order->items ?? []) as $item) {
                    $type = (string) ($item['type'] ?? '');
                    $productId = (int) ($item['item_id'] ?? 0);

                    if (! in_array($type, ['book', 'stationery'], true) || $productId <= 0) {
                        continue;
                    }

                    $key = $type.'_'.$productId;
                    if (isset($unique[$key])) {
                        continue;
                    }

                    if ($this->productReviewPromptService->hasReviewedProduct($user->id, $productId, $type)) {
                        continue;
                    }

                    if (! $this->productReviewPromptService->isProductStillPublic($productId, $type)) {
                        continue;
                    }

                    $unique[$key] = [
                        'product_id' => $productId,
                        'product_type' => $type,
                    ];

                    if ($type === 'book') {
                        $bookIds[] = $productId;
                    } else {
                        $stationeryIds[] = $productId;
                    }

                }
            }

            $ordersPage++;
        }

        if (empty($unique)) {
            return [
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'has_more' => false,
                ],
            ];
        }

        $books = Books::query()
            ->with(['category', 'tags', 'seller'])
            ->whereIn('id', array_values(array_unique($bookIds)))
            ->get()
            ->keyBy('id');

        $stationeries = Stationery::query()
            ->with(['category', 'tags', 'seller'])
            ->whereIn('id', array_values(array_unique($stationeryIds)))
            ->get()
            ->keyBy('id');

        $payload = [];

        $total = count($unique);
        $pageItems = array_slice(array_values($unique), ($page - 1) * $perPage, $perPage);
        $hasMore = $total > ($page * $perPage);

        foreach ($pageItems as $item) {
            $type = $item['product_type'];
            $productId = $item['product_id'];
            $product = $type === 'book'
                ? $books->get($productId)
                : $stationeries->get($productId);

            if (! $product) {
                continue;
            }

            $formatted = ProductPayloadFormatter::format($product, [
                'user' => $user,
                'type' => $type,
                'category_format' => 'title',
            ]);

            if ($formatted) {
                $payload[] = $formatted;
            }
        }

        return [
            'data' => $payload,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $hasMore,
            ],
        ];
    }

    /**
     * @param  array{enabled: bool, amount: int}  $settings
     * @return array{enabled: bool, amount: int, pending_count: int, total_amount: int}
     */
    private function buildReviewCashbackPayload(array $settings, int $pendingCount): array
    {
        $amount = max(0, (int) ($settings['amount'] ?? 0));
        $enabled = (bool) ($settings['enabled'] ?? false);
        $safePendingCount = max(0, $pendingCount);

        return [
            'enabled' => $enabled,
            'amount' => $amount,
            'pending_count' => $safePendingCount,
            'total_amount' => $enabled ? ($amount * $safePendingCount) : 0,
        ];
    }

    public function purchaseDetails(Request $request, $order_id)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (! $order) {
            return $this->err('Buyurtma topilmadi!', 404);
        }

        $order->formatted_created_at = Carbon::parse($order->created_at)->isoFormat('D MMMM YYYY, HH:mm');
        $order->formatted_updated_at = Carbon::parse($order->updated_at)->isoFormat('D MMMM YYYY, HH:mm');
        $this->appendOrderSellerMeta($order);
        $this->appendOrderStatusMeta($order);
        $this->appendDeliveryProgressMeta($order);
        $this->applySignedDeliveryQr($order);
        $this->appendFiscalReceiptMeta($order);

        return response()->json(['status' => 'success', 'data' => [$order]]);
    }

    public function payPendingOrderWithSavedCard(Request $request, int $order_id)
    {
        $request->validate([
            'card_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (! $order) {
            return $this->err('Buyurtma topilmadi!', 404);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (! $card) {
            return $this->err('Karta topilmadi!', 404);
        }

        try {
            $payment = $this->paylovOrderPaymentService->payPendingOrder($order, $user, $card);
            $this->appendFiscalReceiptMeta($order->fresh());

            return response()->json([
                'status' => 'success',
                'message' => ($payment['payment_status'] ?? null) === PaymentStatusCode::HELD->value
                    ? 'Karta mablag‘i vaqtincha ushlab turildi.'
                    : 'To‘lov muvaffaqiyatli qabul qilindi.',
                'data' => $payment,
            ]);
        } catch (\Throwable $e) {
            return $this->err($e->getMessage(), 422);
        }
    }

    public function createPostalResend(Request $request, int $order_id)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return $this->err('Foydalanuvchi topilmadi!', 401);
        }

        $order = Sold::where('user_id', $user->id)->where('id', $order_id)->first();
        if (! $order) {
            return $this->err('Buyurtma topilmadi!', 404);
        }

        try {
            $resendOrder = $this->postalResendService->createResendOrder($order, $user);

            return response()->json([
                'status' => 'success',
                'message' => 'Qayta yuborish uchun yangi buyurtma yaratildi.',
                'data' => [
                    'order_id' => $resendOrder->id,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->err($e->getMessage(), 422);
        }
    }

    private function appendOrderStatusMeta(Sold $order): void
    {
        $order->status_code = $order->status_code;
        $order->payment_status_code = $order->payment_status_code;
        $order->postal_return_status = $order->postal_return_status;
        $order->can_postal_resend = $order->isPostalResendSource();
        $order->postal_resend_fee = (int) ($order->postal_return_fee ?? 0);
        $order->postal_return_note = $order->postal_return_note;
        $order->resend_replacement_order_id = $order->resend_replacement_order_id;
        $order->expected_delivery_at = $order->estimatedDeliveryAt()?->toIso8601String();
        $order->is_delivery_delayed = $order->isDeliveryDelayed();
    }

    private function appendOrderSellerMeta(Sold $order): void
    {
        $items = collect($order->items ?? []);
        $sellerIds = $items->pluck('seller_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($sellerIds->isEmpty()) {
            return;
        }

        $sellers = Seller::query()
            ->whereIn('id', $sellerIds)
            ->get(['id', 'shop_name', 'photo'])
            ->keyBy('id');

        $order->items = $items->map(function ($item) use ($sellers) {
            if (! is_array($item)) {
                return $item;
            }

            $seller = $sellers->get((int) ($item['seller_id'] ?? 0));
            if ($seller) {
                $item['seller_name'] = $item['seller_name'] ?? $seller->shop_name;
                $item['seller_avatar'] = $item['seller_avatar'] ?? $seller->photo;
            }

            return $item;
        })->values()->all();
    }

    private function appendFiscalReceiptMeta(Sold $order): void
    {
        $transaction = Transaction::query()
            ->where('payment_type', 'order')
            ->where('order_id', $order->id)
            ->where('provider', 'paylov')
            ->where('state', 2)
            ->latest('id')
            ->first(['id', 'payment_type', 'provider_transaction_id', 'perform_fiscal_data', 'cancel_fiscal_data', 'provider_response']);

        // Split buyurtmasida ViewPurchase faqat birinchi real to'lov chekini
        // ko'rsatadi. Keyingi cheklar shartnoma jadvaliga tegishli.
        if ($transaction === null) {
            $splitTransactions = Transaction::query()
                ->where('payment_type', 'split')
                ->where('order_id', $order->id)
                ->where('provider', 'paylov')
                ->where('state', 2)
                ->orderBy('id')
                ->get(['id', 'payment_type', 'provider_transaction_id', 'perform_fiscal_data', 'cancel_fiscal_data', 'provider_response']);

            $transaction = $splitTransactions->first(fn (Transaction $candidate) => (int) data_get($candidate->provider_response, 'split_installment_sequence', 0) === 1
            ) ?? $splitTransactions->first();
        }

        $perform = is_array($transaction?->perform_fiscal_data) ? $transaction->perform_fiscal_data : [];
        $cancel = is_array($transaction?->cancel_fiscal_data) ? $transaction->cancel_fiscal_data : [];
        $providerResponse = is_array($transaction?->provider_response) ? $transaction->provider_response : [];

        $statusTransaction = data_get($providerResponse, 'status.result.transactions.0');
        if (! is_array($statusTransaction)) {
            $statusTransaction = [];
        }

        $payTransaction = data_get($providerResponse, 'pay.result');
        if (! is_array($payTransaction)) {
            $payTransaction = [];
        }

        $perform = $perform ?: $statusTransaction ?: $payTransaction;

        $order->has_payment_receipt = filled($perform['qr_code_url'] ?? null);
        $order->payment_receipt_url = $perform['qr_code_url'] ?? null;
        $order->payment_receipt_fiscal_sign = $perform['fiscal_sign'] ?? null;
        $order->payment_receipt_terminal_id = $perform['terminal_id'] ?? null;
        $order->payment_receipt_date = $perform['date'] ?? ($perform['pay_time'] ?? null);
        $order->payment_cancel_receipt_url = $cancel['qr_code_url'] ?? ($statusTransaction['cancel_qr_code_url'] ?? null);

        // ── Backfill ───────────────────────────────────────────────────
        // To'langan buyurtmada chek yo'q bo'lsa — fonda fiskalizatsiya
        // qilamiz (eski buyurtmalar uchun; kuniga 1 marta throttle).
        if (! $order->has_payment_receipt
            && $transaction !== null
            && filled($transaction->provider_transaction_id)
            && config('services.paylov.ofd.enabled', false)
            && \Illuminate\Support\Facades\Cache::add("ofd:backfill:{$transaction->id}", 1, now()->addDay())
        ) {
            if ((string) $transaction->payment_type === 'split') {
                \App\Jobs\RegisterTransactionFiscalReceiptJob::dispatch((int) $transaction->id);
            } else {
                \App\Jobs\RegisterOrderFiscalReceiptJob::dispatch((int) $order->id);
            }
        }
    }

    private function appendDeliveryProgressMeta(Sold $order): void
    {
        $order->loadMissing([
            'fulfillment.hub:id,name,code',
        ]);

        $order->delivery_progress = $this->buildDeliveryProgress($order);
    }

    private function buildDeliveryProgress(Sold $order): array
    {
        $deliveryFlow = match ($order->deliveryType) {
            'postal' => 'postal',
            'pickup', 'instore' => 'pickup',
            default => 'courier',
        };

        $fulfillment = $order->fulfillment;
        $timeline = [];
        $seen = [];

        $push = static function (
            array &$timeline,
            array &$seen,
            string $code,
            string $step,
            ?string $at,
            array $extra = [],
        ): void {
            if (! $at) {
                return;
            }

            $key = $code.'|'.$at;
            if (isset($seen[$key])) {
                return;
            }

            $timeline[] = array_filter(array_merge([
                'code' => $code,
                'step' => $step,
                'at' => $at,
            ], $extra), static fn ($value) => $value !== null);

            $seen[$key] = true;
        };

        $push(
            $timeline,
            $seen,
            'order_created',
            'ordered',
            optional($order->created_at)?->toIso8601String()
        );

        if ($fulfillment) {
            $push($timeline, $seen, 'ready_for_pickup', 'packing', optional($fulfillment->ready_for_pickup_at)?->toIso8601String());
            $push($timeline, $seen, 'arrived_at_hub', 'packing', optional($fulfillment->arrived_at_hub_at)?->toIso8601String());
            $push($timeline, $seen, 'qc_checked', 'packing', optional($fulfillment->qc_checked_at)?->toIso8601String());
            $push($timeline, $seen, 'packed', 'packing', optional($fulfillment->packed_at)?->toIso8601String());
            $push($timeline, $seen, 'labeled', 'packing', optional($fulfillment->labeled_at)?->toIso8601String());

            $push($timeline, $seen, 'picked_from_seller', 'in_transit', optional($fulfillment->picked_from_seller_at)?->toIso8601String());
            $push($timeline, $seen, 'dispatched_to_post', 'in_transit', optional($fulfillment->dispatched_to_post_at)?->toIso8601String());

            $push($timeline, $seen, 'assigned_last_mile', 'handoff', optional($fulfillment->assigned_last_mile_at)?->toIso8601String());
            $push($timeline, $seen, 'out_for_delivery', 'handoff', optional($fulfillment->out_for_delivery_at)?->toIso8601String());
            $push($timeline, $seen, 'delivered', $deliveryFlow === 'postal' ? 'handoff' : 'received', optional($fulfillment->delivered_at)?->toIso8601String());

            foreach ((array) data_get($fulfillment->meta, 'timeline', []) as $row) {
                if (! is_array($row) || empty($row['code']) || empty($row['at'])) {
                    continue;
                }

                $code = (string) $row['code'];
                $step = match ($code) {
                    'ready_for_pickup', 'arrived_at_hub', 'qc_checked', 'packed', 'labeled' => 'packing',
                    'picked_from_seller', 'dispatched_to_post' => 'in_transit',
                    'assigned_last_mile', 'out_for_delivery', 'delivered' => $deliveryFlow === 'postal' && $code === 'delivered'
                        ? 'handoff'
                        : ($code === 'delivered' ? 'received' : 'handoff'),
                    default => null,
                };

                if (! $step) {
                    continue;
                }

                $push($timeline, $seen, $code, $step, (string) $row['at']);
            }
        }

        if ($order->status_code === OrderStatusCode::CUSTOMER_RECEIVED->value) {
            $push(
                $timeline,
                $seen,
                'customer_received',
                'received',
                optional($order->completed_at ?? $order->updated_at)?->toIso8601String()
            );
        }

        usort($timeline, static fn (array $a, array $b) => strcmp((string) ($a['at'] ?? ''), (string) ($b['at'] ?? '')));

        return [
            'delivery_flow' => $deliveryFlow,
            'fulfillment_status' => $fulfillment?->status_code,
            'hub_name' => $fulfillment?->hub?->name,
            'active_step' => $this->resolveDeliveryProgressActiveStep($order, $fulfillment, $deliveryFlow),
            'timeline' => array_values($timeline),
        ];
    }

    private function resolveDeliveryProgressActiveStep(Sold $order, $fulfillment, string $deliveryFlow): string
    {
        $statusCode = $order->status_code;
        $paymentStatusCode = $order->payment_status_code;
        $fulfillmentStatus = $fulfillment?->status_code;

        if ($statusCode === OrderStatusCode::CUSTOMER_RECEIVED->value) {
            return 'received';
        }

        if ($deliveryFlow === 'postal' && $statusCode === OrderStatusCode::DELIVERED->value) {
            return 'handoff';
        }

        if ($deliveryFlow !== 'postal' && $statusCode === OrderStatusCode::DELIVERED->value) {
            return 'received';
        }

        if (in_array($fulfillmentStatus, ['assigned_last_mile', 'out_for_delivery'], true)) {
            return 'handoff';
        }

        if (in_array($fulfillmentStatus, ['picked_from_seller', 'dispatched_to_post'], true) || $statusCode === OrderStatusCode::IN_DELIVERY->value) {
            return 'in_transit';
        }

        if (in_array($fulfillmentStatus, ['ready_for_pickup', 'arrived_at_hub', 'qc_checked', 'packed', 'labeled'], true)
            || $statusCode === OrderStatusCode::PACKING->value) {
            return 'packing';
        }

        if ($paymentStatusCode === PaymentStatusCode::CARD_PENDING->value && $statusCode === OrderStatusCode::PENDING->value) {
            return 'ordered';
        }

        return 'ordered';
    }
}
