<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Sold;
use App\Models\UserCard;
use App\Services\PaylovOrderPaymentService;
use App\Services\PaylovService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CardController extends Controller
{
    public function __construct(
        private readonly PaylovOrderPaymentService $paylovOrderPaymentService,
    ) {
    }

    private function success(array $payload = [], int $status = 200)
    {
        return response()->json(array_merge([
            'status' => 'success',
            'ok' => true,
        ], $payload), $status);
    }

    private function error(string $message, int $status = 400, array $extra = [])
    {
        return response()->json(array_merge([
            'status' => 'error',
            'ok' => false,
            'message' => $message,
            'error' => $message,
        ], $extra), $status);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $cards = $user->cards()
            ->where('is_verified', true)
            ->where(function ($query) {
                $query->whereNull('is_temporary')
                    ->orWhere('is_temporary', false);
            })
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        $this->refreshStoredCardsState($cards);

        $cards = $cards
            ->map(fn (UserCard $card) => $this->serializeCard($card))
            ->values();

        return $this->success(['data' => $cards]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|size:16',
            'expire' => 'required|string|size:4',
            'remember_card' => 'sometimes|boolean',
            'order_id' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $rememberCard = (bool) $request->boolean('remember_card', true);
        $orderId = $request->integer('order_id') ?: null;
        $rawNumber = $request->string('number')->toString();
        $maskedNumber = $this->maskCardNumber($rawNumber);
        $expire = $request->string('expire')->toString();
        $fingerprint = $this->fingerprintCardNumber($rawNumber);

        if ($this->userAlreadyHasCard($user->id, $fingerprint, $maskedNumber, $expire)) {
            return $this->error('Bu karta allaqachon ulangan.', 422, [
                'error_code' => 'card_exists',
            ]);
        }

        if ($this->isCardLinkedToAnotherAccount($user->id, $fingerprint, $maskedNumber, $expire)) {
            return $this->cardAlreadyLinkedElsewhereError();
        }

        if ($orderId) {
            $order = Sold::query()
                ->where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return $this->error('Buyurtma topilmadi.', 404);
            }

            if ($order->payment_status_code !== PaymentStatusCode::CARD_PENDING->value) {
                return $this->error('Bu buyurtma karta to‘lovini kutmayapti.');
            }
        }

        try {
            $existingPending = $this->findPendingPaylovCard($user->id, $maskedNumber, $orderId);
            if ($existingPending) {
                return $this->success([
                    'message' => 'Bu karta uchun SMS kod allaqachon yuborilgan. Avvalgi kodni kiriting.',
                    'data' => [
                        'token' => (string) $existingPending->id,
                        'verification_id' => (string) $existingPending->id,
                        'provider_card_id' => (string) $existingPending->provider_card_id,
                        'number' => $existingPending->card_number,
                        'otp_sent_phone' => $existingPending->phone_number,
                    ],
                ]);
            }

            $normalizedExpire = mb_substr($expire, 2, 2) . mb_substr($expire, 0, 2);
            $userPhoneNumber = $this->normalizeUserPhoneForPaylov($user->phone_number);

            $response = PaylovService::make()->createUserCard(
                (string) $user->id,
                $rawNumber,
                $normalizedExpire,
                $userPhoneNumber,
            );

            $result = $response['result'] ?? [];
            $providerCardId = (string) ($result['cid'] ?? '');
            if ($providerCardId === '') {
                throw new RuntimeException('Paylov card id qaytarmadi.');
            }

            $card = UserCard::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'paylov',
                    'provider_card_id' => $providerCardId,
                ],
                [
                    'card_number' => $maskedNumber,
                    'card_fingerprint' => $fingerprint,
                    'expire_date' => $expire,
                    'phone_number' => $result['otpSentPhone'] ?? $userPhoneNumber ?? $user->phone_number,
                    'token' => '',
                    'is_verified' => false,
                    'is_default' => false,
                    'is_temporary' => !$rememberCard,
                    'pending_order_id' => $orderId,
                    'provider_meta' => [
                        'create' => $response,
                    ],
                ],
            );

            return $this->success([
                'message' => 'Karta qo‘shildi. Endi SMS kodni kiriting.',
                'data' => [
                    'token' => (string) $card->id,
                    'verification_id' => (string) $card->id,
                    'provider_card_id' => $providerCardId,
                    'number' => $card->card_number,
                    'otp_sent_phone' => $result['otpSentPhone'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof QueryException && $this->isCardFingerprintConflict($e)) {
                return $this->cardAlreadyLinkedElsewhereError();
            }

            if (str_contains($e->getMessage(), 'otp_code_already_sent')) {
                $existingPending = $this->findPendingPaylovCard($user->id, $maskedNumber, $orderId);
                if ($existingPending) {
                    return $this->success([
                        'message' => 'Bu karta uchun SMS kod allaqachon yuborilgan. Avvalgi kodni kiriting.',
                        'data' => [
                            'token' => (string) $existingPending->id,
                            'verification_id' => (string) $existingPending->id,
                            'provider_card_id' => (string) $existingPending->provider_card_id,
                            'number' => $existingPending->card_number,
                            'otp_sent_phone' => $existingPending->phone_number,
                        ],
                    ]);
                }

                return $this->error('Bu karta uchun SMS kod allaqachon yuborilgan. Bir ozdan keyin qayta urinib ko‘ring.', 429);
            }

            Log::warning('[Paylov] Card create failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required_without:verification_id|string',
            'verification_id' => 'required_without:token|string',
            'code' => 'required|string|size:6',
            'card_name' => 'sometimes|string|max:120',
        ]);

        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $verificationId = (int) ($request->verification_id ?? $request->token);

        $card = $user->cards()->find($verificationId);
        if (!$card) {
            return $this->error('Karta tasdiqlash sessiyasi topilmadi.', 404);
        }

        try {
            if ($this->isCardLinkedToAnotherAccount(
                $user->id,
                $card->card_fingerprint,
                (string) $card->card_number,
                (string) $card->expire_date,
                $card->id,
            )) {
                try {
                    if (filled($card->provider_card_id)) {
                        PaylovService::make()->deleteUserCard((string) $card->provider_card_id);
                    }
                } catch (\Throwable $e) {
                    Log::warning('[Paylov] Duplicate card cleanup failed', [
                        'card_id' => $card->id,
                        'provider_card_id' => $card->provider_card_id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $card->delete();

                return $this->cardAlreadyLinkedElsewhereError();
            }

            $confirm = PaylovService::make()->confirmUserCard(
                (string) $card->provider_card_id,
                $request->string('code')->toString(),
                $request->string('card_name')->toString() ?: null,
            );

            $result = $confirm['result'] ?? [];
            $card->fill([
                'is_verified' => true,
                'card_name' => $result['cardName'] ?? ($card->card_name ?: null),
                'card_fingerprint' => $card->card_fingerprint,
                'vendor' => $result['vendor'] ?? ($card->vendor ?: null),
                'processing' => $result['processing'] ?? ($card->processing ?: null),
                'provider_meta' => array_merge($card->provider_meta ?? [], [
                    'confirm' => $confirm,
                ]),
            ]);

            $paylov = PaylovService::make();
            $paylov->syncUserCard($card);
            $cardState = $paylov->cardPaymentState($card, false);

            if (!$cardState['can_pay']) {
                try {
                    $paylov->deleteUserCard((string) $card->provider_card_id);
                } catch (\Throwable $e) {
                    Log::warning('[Paylov] Invalid card cleanup failed', [
                        'card_id' => $card->id,
                        'provider_card_id' => $card->provider_card_id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $card->delete();

                return $this->error((string) $cardState['error_code'], 422, [
                    'error_code' => $cardState['error_code'],
                ]);
            }

            if ($this->isCardLinkedToAnotherAccount(
                $user->id,
                $card->card_fingerprint,
                (string) $card->card_number,
                (string) $card->expire_date,
                $card->id,
            )) {
                try {
                    $paylov->deleteUserCard((string) $card->provider_card_id);
                } catch (\Throwable $e) {
                    Log::warning('[Paylov] Duplicate card post-confirm cleanup failed', [
                        'card_id' => $card->id,
                        'provider_card_id' => $card->provider_card_id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $card->delete();

                return $this->cardAlreadyLinkedElsewhereError();
            }

            if (!$card->is_temporary && !$user->cards()->where('is_default', true)->exists()) {
                $card->is_default = true;
            }

            $paymentPayload = null;
            if ($card->pending_order_id) {
                $order = Sold::query()
                    ->where('id', $card->pending_order_id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$order) {
                    throw new RuntimeException('To‘lov uchun buyurtma topilmadi.');
                }

                $paymentPayload = $this->paylovOrderPaymentService->payPendingOrder($order, $user, $card);
            }

            $card->pending_order_id = null;
            $card->save();

            if ($card->is_temporary) {
                try {
                    PaylovService::make()->deleteUserCard((string) $card->provider_card_id);
                } catch (\Throwable $e) {
                    Log::warning('[Paylov] Temporary card delete failed', [
                        'card_id' => $card->id,
                        'provider_card_id' => $card->provider_card_id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $card->delete();
            }

            return $this->success([
                'message' => $paymentPayload !== null
                    ? 'To‘lov muvaffaqiyatli qabul qilindi.'
                    : 'Karta muvaffaqiyatli tasdiqlandi va bog‘landi.',
                'card' => $this->serializeCard($card),
                'payment' => $paymentPayload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Paylov] Card verify failed', [
                'user_id' => $user->id,
                'card_id' => $card->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 422);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        /** @var UserCard|null $card */
        $card = $user->cards()->find($id);
        if (!$card) {
            return $this->error('Karta topilmadi.', 404);
        }

        try {
            if ($card->provider === 'paylov' && filled($card->provider_card_id)) {
                PaylovService::make()->deleteUserCard((string) $card->provider_card_id);
            }
        } catch (\Throwable $e) {
            Log::warning('[Paylov] Card remote delete failed', [
                'user_id' => $user->id,
                'card_id' => $card->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error('Kartani Paylov tomondan o‘chirib bo‘lmadi. Iltimos, qayta urinib ko‘ring.', 422, [
                'error_code' => 'card_remote_delete_failed',
            ]);
        }

        DB::transaction(function () use ($card, $user) {
            $wasDefault = (bool) $card->is_default;
            $card->delete();

            if ($wasDefault) {
                $nextDefault = $user->cards()
                    ->where('is_verified', true)
                    ->latest('id')
                    ->first();

                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }
        });

        return $this->success([
            'message' => 'Karta o‘chirildi',
        ]);
    }

    private function serializeCard(UserCard $card): array
    {
        $state = PaylovService::make()->cardPaymentState($card, false);
        $brandType = $this->resolveBrandType($card);

        return [
            'id' => $card->id,
            'provider' => $card->provider,
            'provider_card_id' => $card->provider_card_id,
            'card_number' => $card->card_number,
            'masked_number' => $card->card_number,
            'card_name' => $card->card_name,
            'expire_date' => $card->expire_date,
            'vendor' => $card->vendor,
            'processing' => $card->processing,
            'brand_type' => $brandType,
            'is_active' => $state['is_active'],
            'is_expired' => $state['is_expired'],
            'sms_info' => $state['sms_info'],
            'status_message' => $state['status_message'],
            'payment_error_code' => $state['error_code'],
            'is_default' => (bool) $card->is_default,
            'created_at' => optional($card->created_at)?->toIso8601String(),
        ];
    }

    private function maskCardNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if (strlen($digits) < 12) {
            return $digits;
        }

        return substr($digits, 0, 6)
            . str_repeat('*', max(0, strlen($digits) - 10))
            . substr($digits, -4);
    }

    private function fingerprintCardNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

    private function normalizeUserPhoneForPaylov(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 9) {
            return '+998' . $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            return '+' . $digits;
        }

        return null;
    }

    private function findPendingPaylovCard(int $userId, string $maskedNumber, ?int $orderId): ?UserCard
    {
        return UserCard::query()
            ->where('user_id', $userId)
            ->where('provider', 'paylov')
            ->where('is_verified', false)
            ->where('card_number', $maskedNumber)
            ->when(
                $orderId !== null,
                fn ($query) => $query->where('pending_order_id', $orderId),
                fn ($query) => $query->whereNull('pending_order_id'),
            )
            ->whereNotNull('provider_card_id')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest('id')
            ->first();
    }

    private function isCardLinkedToAnotherAccount(
        int $userId,
        ?string $fingerprint,
        string $maskedNumber,
        string $expireDate,
        ?int $ignoreCardId = null,
    ): bool {
        return UserCard::query()
            ->where('user_id', '!=', $userId)
            ->when($ignoreCardId !== null, fn ($query) => $query->where('id', '!=', $ignoreCardId))
            ->where(function ($query) use ($fingerprint, $maskedNumber, $expireDate) {
                if (filled($fingerprint)) {
                    $query->where('card_fingerprint', $fingerprint)
                        ->orWhere(function ($legacyQuery) use ($maskedNumber, $expireDate) {
                            $legacyQuery->where('card_number', $maskedNumber)
                                ->where('expire_date', $expireDate);
                        });

                    return;
                }

                $query->where('card_number', $maskedNumber)
                    ->where('expire_date', $expireDate);
            })
            ->exists();
    }

    private function userAlreadyHasCard(
        int $userId,
        ?string $fingerprint,
        string $maskedNumber,
        string $expireDate,
    ): bool {
        return UserCard::query()
            ->where('user_id', $userId)
            ->where('is_verified', true)
            ->where(function ($query) use ($fingerprint, $maskedNumber, $expireDate) {
                if (filled($fingerprint)) {
                    $query->where('card_fingerprint', $fingerprint)
                        ->orWhere(function ($legacyQuery) use ($maskedNumber, $expireDate) {
                            $legacyQuery->where('card_number', $maskedNumber)
                                ->where('expire_date', $expireDate);
                        });

                    return;
                }

                $query->where('card_number', $maskedNumber)
                    ->where('expire_date', $expireDate);
            })
            ->exists();
    }

    private function cardAlreadyLinkedElsewhereError()
    {
        return $this->error('Bu karta boshqa akkauntga bog‘langan.', 409, [
            'error_code' => 'card_attached_to_another_account',
        ]);
    }

    private function isCardFingerprintConflict(QueryException $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'card_fingerprint')
            || str_contains($message, 'user_cards_card_fingerprint_unique')
            || ($e->getCode() === '23000' && str_contains($message, 'duplicate'));
    }

    private function refreshStoredCardsState($cards): void
    {
        foreach ($cards as $card) {
            if (!$card instanceof UserCard) {
                continue;
            }

            if ($card->provider !== 'paylov' || blank($card->provider_card_id)) {
                continue;
            }

            if (!$this->shouldRefreshCardState($card)) {
                continue;
            }

            try {
                PaylovService::make()->syncUserCard($card);
            } catch (\Throwable $e) {
                Log::warning('[Paylov] Card state refresh failed', [
                    'card_id' => $card->id,
                    'provider_card_id' => $card->provider_card_id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function shouldRefreshCardState(UserCard $card): bool
    {
        $fetchedAt = data_get($card->provider_meta, 'single_card_fetched_at');
        if (!is_string($fetchedAt) || trim($fetchedAt) === '') {
            return true;
        }

        try {
            return now()->diffInMinutes($fetchedAt) >= 15;
        } catch (\Throwable) {
            return true;
        }
    }

    private function resolveBrandType(UserCard $card): ?string
    {
        $signals = [
            (string) ($card->vendor ?? ''),
            (string) ($card->processing ?? ''),
            (string) data_get($card->provider_meta, 'single_card.result.card.vendor', ''),
            (string) data_get($card->provider_meta, 'single_card.result.card.processing', ''),
        ];

        foreach ($signals as $signal) {
            $normalized = mb_strtolower(trim($signal));
            if ($normalized === '') {
                continue;
            }

            if (str_contains($normalized, 'uzcard')) {
                return 'uzcard';
            }

            if (str_contains($normalized, 'humo')) {
                return 'humo';
            }

            if (str_contains($normalized, 'visa')) {
                return 'visa';
            }

            if (str_contains($normalized, 'mastercard') || str_contains($normalized, 'master card')) {
                return 'mastercard';
            }
        }

        return null;
    }
}
