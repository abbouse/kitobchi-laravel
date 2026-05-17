<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MysteryBoxPlan;
use App\Models\GiftCertificate;
use App\Models\MysteryBoxDelivery;
use App\Models\MysteryBoxSubscription;
use App\Models\ProjectSetting;
use App\Models\UserCard;
use App\Services\PaylovPayablePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShopApiController extends Controller
{
    public function __construct(
        private readonly PaylovPayablePaymentService $paylovPayablePaymentService,
    ) {
    }

    private function localizedValue($model, string $base, string $locale)
    {
        $preferred = data_get($model, "{$base}_{$locale}");
        if (!empty($preferred)) {
            return $preferred;
        }

        return data_get($model, "{$base}_uz")
            ?? data_get($model, "{$base}_ru")
            ?? data_get($model, "{$base}_en");
    }

    private function formatLocationPayload(object $location, object $user): array
    {
        return [
            'fullName'    => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
            'phoneNumber' => $user->phone_number,
            'fullAddress' => $location->fullAddress ?? null,
            'lat'         => $location->lat ?? null,
            'lon'         => $location->lon ?? null,
            // old keys for backward compatibility
            'name'        => trim(($user->name ?? '') . ' ' . ($user->lastname ?? '')),
            'phone'       => $user->phone_number,
        ];
    }

    private function giftCertificateOptions(): array
    {
        $settings = ProjectSetting::query()->first();
        $raw = $settings?->gift_certificate_options;

        $options = collect(is_array($raw) ? $raw : [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value >= 1000)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return !empty($options) ? $options : [300000, 500000, 1000000];
    }

    // ── GET /api/shop/info ────────────────────────────────────────────────────
    // Mystery box planlar + gift cert options
    public function info()
    {
        $locale = Auth::guard('user')->user()?->locale ?? 'uz';

        $mysteryPlans = MysteryBoxPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'name'           => $this->localizedValue($p, 'name', $locale),
                'months'         => $p->months,
                'price_uzs'      => $p->price_uzs,
                'price_per_month'=> $p->price_per_month,
                'books_per_month'=> $p->books_per_month,
                'description'    => $this->localizedValue($p, 'description', $locale),
                'is_popular'     => $p->months === 3, // 3 oylik — popular
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'mystery_box' => [
                    'plans' => $mysteryPlans,
                    // Features Flutter tomonida hardcode — bu yerda faqat planlar
                ],
                'gift_certificate' => [
                    'options' => $this->giftCertificateOptions(),
                    // Features Flutter tomonida hardcode — bu yerda faqat narxlar
                ],
            ],
        ]);
    }

    // ── POST /api/shop/gift-certificate/buy ───────────────────────────────────
    // Gift cert yaratish. To'lov saved-card oqimida alohida yakunlanadi.
    public function buyCertificate(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $request->validate([
            'nominal_uzs' => ['required', 'integer', Rule::in($this->giftCertificateOptions())],
            'for_self'    => 'required|boolean',
            // Faqat do'stga sovg'a qilinganda message keladi
            'message'     => 'nullable|string|max:200',
        ]);

        $forSelf = $request->boolean('for_self');

        $cert = GiftCertificate::create([
            'buyer_user_id'    => $user->id,
            'recipient_user_id'=> $forSelf ? $user->id : null,
            'code'             => GiftCertificate::generateCode(),
            'nominal_uzs'      => $request->nominal_uzs,
            'status'           => GiftCertificate::STATUS_PENDING,
            // Do'stga sovg'a: message saqlandi, recipient keyinchalik activate orqali o'tadi
            'message'          => !$forSelf ? $request->message : null,
        ]);

        return response()->json([
            'status'     => 'success',
            'cert_id'    => $cert->id,
            'code'       => $cert->code,
            'amount_uzs' => $cert->nominal_uzs,
        ], 201);
    }

    // ── POST /api/shop/mystery-box/subscribe ──────────────────────────────────
    // Mystery box obuna yaratish. To'lov saved-card oqimida alohida yakunlanadi.
    public function subscribeMysteryBox(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $request->validate([
            'plan_id' => 'required|integer|exists:mystery_box_plans,id',
            'dispatch_type' => 'nullable|in:courier,postal,pickup',
        ]);

        $plan = MysteryBoxPlan::where('id', $request->plan_id)
            ->where('is_active', true)
            ->firstOrFail();

        $location = DB::table('locations')->where('id', $user->mainAddressID)->first();
        if (!$location) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Avval yetkazib berish manzilini belgilang',
            ], 400);
        }

        $sub = MysteryBoxSubscription::create([
            'user_id'        => $user->id,
            'plan_id'        => $plan->id,
            'address'        => $this->formatLocationPayload($location, $user),
            'preferred_dispatch_type' => $request->input('dispatch_type', MysteryBoxDelivery::DISPATCH_COURIER),
            'status'         => MysteryBoxSubscription::STATUS_PENDING,
            'total_months'   => $plan->months,
            'books_per_month'=> $plan->books_per_month,
            'price_uzs'      => $plan->price_uzs,
        ]);

        return response()->json([
            'status'         => 'success',
            'subscription_id'=> $sub->id,
            'amount_uzs' => $sub->price_uzs,
        ], 201);
    }

    public function payGiftCertificateWithSavedCard(Request $request, int $certId)
    {
        $request->validate([
            'card_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $cert = GiftCertificate::where('id', $certId)
            ->where('buyer_user_id', $user->id)
            ->first();

        if (!$cert) {
            return response()->json(['status' => 'error', 'message' => 'Sertifikat topilmadi'], 404);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Karta topilmadi'], 404);
        }

        try {
            $payment = $this->paylovPayablePaymentService->payPendingGiftCertificate($cert, $user, $card);

            return response()->json([
                'status' => 'success',
                'message' => 'Sertifikat uchun to‘lov muvaffaqiyatli qabul qilindi.',
                'data' => $payment,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function payMysteryBoxWithSavedCard(Request $request, int $id)
    {
        $request->validate([
            'card_id' => 'required|integer|min:1',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $subscription = MysteryBoxSubscription::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json(['status' => 'error', 'message' => 'Obuna topilmadi'], 404);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()
            ->where('id', (int) $request->card_id)
            ->where('is_verified', true)
            ->first();

        if (!$card) {
            return response()->json(['status' => 'error', 'message' => 'Karta topilmadi'], 404);
        }

        try {
            $payment = $this->paylovPayablePaymentService->payPendingMysteryBox($subscription, $user, $card);

            return response()->json([
                'status' => 'success',
                'message' => 'Obuna uchun to‘lov muvaffaqiyatli qabul qilindi.',
                'data' => $payment,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // ── POST /api/shop/gift-certificate/activate ──────────────────────────────
    // Gift cert kodini kiritib aktivlashtirish
    public function activateCertificate(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $request->validate(['code' => 'required|string']);

        $cert = GiftCertificate::where('code', strtoupper(trim($request->code)))->first();

        if (!$cert) {
            return response()->json(['status' => 'error', 'message' => 'Sertifikat topilmadi'], 404);
        }

        $result = $cert->activate($user);

        if (!$result['ok']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
            'cert'    => $result['cert'],
        ]);
    }
    // ── GET /api/shop/mystery-box/subscription/{id} ───────────────────────────
    // Obuna detali + deliveries + o'tgan obunalar
    public function subscriptionDetail(Request $request, int $id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $sub = MysteryBoxSubscription::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['plan:id,name_uz,name_ru,name_en,name_ja,books_per_month', 'deliveries' => fn($q) => $q->orderBy('month_number')])
            ->first();

        if (!$sub) {
            return response()->json(['status' => 'error', 'message' => 'Topilmadi'], 404);
        }

        $addr = $sub->address;

        $data = [
            'id'               => $sub->id,
            'status'           => $sub->status,
            'plan_name'        => $this->localizedValue($sub->plan, 'name', $user->locale ?? 'uz') ?? '',
            'total_months'     => $sub->total_months,
            'delivered_months' => $sub->delivered_months,
            'books_per_month'  => $sub->books_per_month,
            'price_uzs'        => $sub->price_uzs,
            'started_at'       => $sub->started_at?->format('d.m.Y'),
            'ends_at'          => $sub->ends_at?->format('d.m.Y'),
            'next_delivery_at' => $sub->next_delivery_at?->format('d.m.Y'),
            'preferred_dispatch_type' => $sub->preferred_dispatch_type,
            'has_address'      => !empty($addr['fullAddress'] ?? null),
            'deliveries'       => $sub->deliveries->map(fn($d) => [
                'month_number'  => $d->month_number,
                'status'        => $d->status,
                'status_label'  => $d->status_label,
                'dispatch_type' => $d->dispatch_type,
                'dispatch_label' => $d->dispatch_type_label,
                'tracking_note' => $d->tracking_note,
                'planned_for_date' => optional($d->planned_for_date)?->format('d.m.Y'),
                'prepared_at'   => $d->prepared_at?->format('d.m.Y'),
                'ready_at'      => $d->ready_at?->format('d.m.Y'),
                'shipped_at'    => $d->shipped_at?->format('d.m.Y'),
                'arrived_to_post_at' => $d->arrived_to_post_at?->format('d.m.Y H:i'),
                'out_for_delivery_at' => $d->out_for_delivery_at?->format('d.m.Y H:i'),
                'delivered_at'  => $d->delivered_at?->format('d.m.Y H:i'),
                'customer_received_at' => $d->customer_received_at?->format('d.m.Y H:i'),
                'is_final' => $d->is_final,
            ])->values(),
        ];

        // O'tgan obunalar (completed / cancelled)
        $past = MysteryBoxSubscription::where('user_id', $user->id)
            ->where('id', '!=', $id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with('plan:id,name_uz,name_ru,name_en,name_ja')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'status'           => $s->status,
                'plan_name'        => $this->localizedValue($s->plan, 'name', $user->locale ?? 'uz') ?? '',
                'total_months'     => $s->total_months,
                'delivered_months' => $s->delivered_months,
                'price_uzs'        => $s->price_uzs,
                'started_at'       => $s->started_at?->format('d.m.Y'),
                'ends_at'          => $s->ends_at?->format('d.m.Y'),
            ]);

        $data['past_subscriptions'] = $past;

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // ── POST /api/shop/mystery-box/update-address ─────────────────────────────
    // Obuna uchun manzil yangilash (manzilosiz holatdan chiqish)
    public function updateSubscriptionAddress(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $request->validate([
            'subscription_id' => 'required|integer',
            'location_id'     => 'required|integer',
        ]);

        $sub = MysteryBoxSubscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$sub) {
            return response()->json(['status' => 'error', 'message' => 'Obuna topilmadi'], 404);
        }

        $location = DB::table('locations')
            ->where('id', $request->location_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$location) {
            return response()->json(['status' => 'error', 'message' => 'Manzil topilmadi'], 404);
        }

        $sub->update([
            'address' => $this->formatLocationPayload($location, $user),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Manzil yangilandi']);
    }
}
