<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MyCart;
use App\Models\FavouriteProducts;
use App\Models\Books;
use App\Models\ProjectSetting;
use App\Models\Stationery;
use App\Services\SmsService;
use App\Services\TelegramOidcService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private const SEND_COOLDOWN_SECONDS = 60;
    private const SEND_LIMIT_WINDOW_SECONDS = 900;
    private const SEND_MAX_ATTEMPTS = 5;
    private const VERIFY_MAX_ATTEMPTS = 5;
    private const VERIFY_LOCK_SECONDS = 600;
    private const DEFAULT_TEST_CODE = '222222';

    public function __construct(private readonly SmsService $smsService)
    {
    }

    public function telegramConfig(TelegramOidcService $telegramOidcService)
    {
        return response()->json([
            'status' => 'success',
            'data' => $telegramOidcService->publicConfig(ProjectSetting::first()),
        ]);
    }

    /**
     * Native SDK dan kelgan id_token ni qabul qilib, userni login/register qiladi.
     * Body: { id_token, device_id?, device_name?, platform?, fcm_token?, guest_cart?, guest_favorites? }
     */
    public function telegramLogin(Request $request, TelegramOidcService $telegramOidcService)
    {
        $request->validate([
            'id_token'    => 'required|string',
            'device_id'   => 'nullable|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'platform'    => 'nullable|string|max:50',
            'fcm_token'   => 'nullable|string|max:255',
        ]);

        $settings = ProjectSetting::first();
        if (!($settings?->telegram_login_enabled ?? false)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Telegram login hozircha o‘chiq.",
            ], 403);
        }

        try {
            $claims = $telegramOidcService->validateIdToken(
                (string) $request->input('id_token'),
                $telegramOidcService->clientId($settings),
            );

            // ── O'zbekiston (+998) raqamlari uchun cheklov ─────────────
            // Faqat 12 raqamli, 998 bilan boshlanadigan UZ raqamlar qabul qilinadi.
            if (!$this->isUzbekistanTelegramPhone($claims)) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'tg_err_phone_not_uz',
                    'message'    => "Faqat O'zbekiston raqamlari (+998) qabul qilinadi.",
                ], 422);
            }

            $user = DB::transaction(function () use ($claims) {
                return $this->resolveTelegramUser($claims);
            });

            if ($blocked = $this->blockedUserResponse($user)) {
                return $blocked;
            }

            if ($request->has('guest_cart') || $request->has('guest_favorites')) {
                $this->syncGuestData($request, $user);
            }

            $plainTextToken = $this->issueUserToken($user, $request);

            return $this->successUserResponse($user->fresh(['location']), $plainTextToken);
        } catch (\Throwable $e) {
            Log::warning('Telegram auth failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Telegram login amalga oshmadi.',
            ], 422);
        }
    }

    /**
     * Telegram qaytargan claims dan telefonni olib UZ formatga tekshiradi.
     * UZ raqami: 12 ta raqam, "998" bilan boshlanadi, keyingi 9 ta raqam.
     */
    private function isUzbekistanTelegramPhone(array $claims): bool
    {
        $phone = preg_replace('/\D+/', '', (string) ($claims['phone_number'] ?? ''));

        if ($phone === '') {
            return false;
        }

        return (bool) preg_match('/^998\d{9}$/', $phone);
    }

    // ─────────────────────────────────────────────────────────────────
    // STORE — SMS yuborish va kodni tasdiqlash
    // ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $randomString = Str::random(10);
        $phone_number = str_replace([' ', '-'], '', $request->phone_number);

        if (!preg_match('/^\d{12}$/', $phone_number)) {
            return response()->json([
                'status' => 'error',
                'message' => "Telefon raqami noto'g'ri formatda yuborildi",
            ], 400);
        }

        // ── 1-BOSQICH: SMS yuborish ───────────────────────────────────
        if (strlen($phone_number) == 12 && is_null($request->verifyCode)) {
            if ($limitResponse = $this->ensureCanSendCode($phone_number)) {
                return $limitResponse;
            }

            $isTestPhone = $this->isTestPhone($phone_number);
            $verifyCode = $isTestPhone
                ? $this->testVerifyCode()
                : rand(100001, 999999);

            if (!$isTestPhone) {
                try {
                    $this->smsService->send(
                        $phone_number,
                        "<#> Kitobchi ilovasida tasdiqlash uchun kod: $verifyCode. $randomString"
                    );
                } catch (\Throwable $e) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => $e->getMessage(),
                    ], 503);
                }
            }

            $user = User::where('phone_number', $phone_number)
                ->where('isDeleted', 'no')
                ->first();

            if ($user && ($blocked = $this->blockedUserResponse($user))) {
                return $blocked;
            }

            if (!$user) {
                User::create([
                    'phone_number' => $phone_number,
                    'verifyCode'   => $verifyCode,
                    'password'     => bcrypt('kitobchi122'),
                ]);
            } else {
                $user->update(['verifyCode' => $verifyCode]);
            }

            $this->recordCodeSend($phone_number);

            return response()->json(['status' => 'success', 'message' => 'Kod yuborildi'], 201);
        }

        // ── 2-BOSQICH: Kodni tasdiqlash ───────────────────────────────
        if ($limitResponse = $this->ensureCanVerifyCode($phone_number)) {
            return $limitResponse;
        }

        $user = User::where('phone_number', $phone_number)
            ->where('isDeleted', 'no')
            ->first();

        if ($user && trim((string) $user->verifyCode) === trim((string) $request->verifyCode)) {
            if ($blocked = $this->blockedUserResponse($user)) {
                return $blocked;
            }

            // Token yaratish
            $tokenResult    = $user->createToken('user_token');
            $plainTextToken = $tokenResult->plainTextToken;
            $hashedToken    = hash('sha256', explode('|', $plainTextToken)[1]);

            // Qurilmani saqlash
            if ($request->has('device_id')) {
                app(\App\Services\FcmRecipientService::class)->claimToken(
                    'user',
                    (int) $user->id,
                    $request->fcm_token,
                );
                DB::table('connected_devices')->updateOrInsert(
                    ['device_id' => $request->device_id, 'user_type' => 'user'],
                    [
                        'user_id'     => $user->id,
                        'user_type'   => 'user',
                        'token'       => $hashedToken,
                        'fcm_token'   => $request->fcm_token,
                        'device_name' => Str::limit(trim((string) ($request->device_name ?? 'Unknown Device')), 64, ''),
                        'platform'    => Str::limit(trim((string) ($request->platform ?? 'Unknown')), 64, ''),
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );
            }

            // Eskirgan tokenlarni tozalash
            $activeTokens = DB::table('connected_devices')
                ->where('user_id', $user->id)
                ->where('user_type', 'user')
                ->pluck('token')
                ->toArray();
            $user->tokens()->whereNotIn('token', $activeTokens)->delete();

            $user->update([
                'verifyCode' => null,
                'phone_verified_at' => now(),
            ]);
            $this->clearVerifyAttemptState($phone_number);

            // ── ✅ GUEST DATA SYNC ────────────────────────────────────
            // Token yaratilgandan keyin, response dan oldin
            // guest_cart va guest_favorites ni darhol serverga biriktiramiz.
            // Alohida endpoint kerak emas — bitta requestda bajariladi.
            if ($request->has('guest_cart') || $request->has('guest_favorites')) {
                $this->syncGuestData($request, $user);
            }
            // ─────────────────────────────────────────────────────────

            // cartItemCount ni sync dan keyin qayta hisoblaymiz
            return $this->successUserResponse($user->fresh(['location']), $plainTextToken);
        }

        $remainingAttempts = $this->recordFailedVerifyAttempt($phone_number);

        return response()->json([
            'status'  => 'error',
            'message' => 'Tekshiruv kodi xato kiritildi',
            'remaining_attempts' => $remainingAttempts,
        ], 400);
    }

    private function ensureCanSendCode(string $phoneNumber)
    {
        $cooldownKey = $this->smsSendCooldownKey($phoneNumber);
        $windowKey = $this->smsSendWindowKey($phoneNumber);

        if (Cache::has($cooldownKey)) {
            $seconds = $this->secondsUntil(Cache::get($cooldownKey));

            return response()->json([
                'status' => 'error',
                'message' => "Kodni qayta yuborish uchun {$seconds} soniya kuting",
                'retry_after' => $seconds,
            ], 429);
        }

        $attempts = (int) Cache::get($windowKey, 0);
        if ($attempts >= self::SEND_MAX_ATTEMPTS) {
            $seconds = $this->secondsUntil(
                Cache::get($this->smsSendWindowTimerKey($phoneNumber))
            );

            return response()->json([
                'status' => 'error',
                'message' => "Juda ko'p urinish bo'ldi. {$seconds} soniyadan keyin qayta urinib ko'ring",
                'retry_after' => $seconds,
            ], 429);
        }

        return null;
    }

    private function recordCodeSend(string $phoneNumber): void
    {
        $windowKey = $this->smsSendWindowKey($phoneNumber);
        $windowTimerKey = $this->smsSendWindowTimerKey($phoneNumber);
        $cooldownKey = $this->smsSendCooldownKey($phoneNumber);
        $windowExpiresAt = now()->addSeconds(self::SEND_LIMIT_WINDOW_SECONDS);
        $cooldownExpiresAt = now()->addSeconds(self::SEND_COOLDOWN_SECONDS);

        $attempts = (int) Cache::get($windowKey, 0);
        Cache::put($windowKey, $attempts + 1, $windowExpiresAt);
        Cache::put($windowTimerKey, $windowExpiresAt->timestamp, $windowExpiresAt);
        Cache::put($cooldownKey, $cooldownExpiresAt->timestamp, $cooldownExpiresAt);
    }

    private function ensureCanVerifyCode(string $phoneNumber)
    {
        $lockKey = $this->verifyLockKey($phoneNumber);

        if (!Cache::has($lockKey)) {
            return null;
        }

        $seconds = $this->secondsUntil(Cache::get($lockKey));

        return response()->json([
            'status' => 'error',
            'message' => "Ko'p marotaba noto'g'ri kod kiritildi. {$seconds} soniya kutib qayta urinib ko'ring",
            'retry_after' => $seconds,
        ], 429);
    }

    private function recordFailedVerifyAttempt(string $phoneNumber): int
    {
        $attemptKey = $this->verifyAttemptKey($phoneNumber);
        $lockKey = $this->verifyLockKey($phoneNumber);
        $attempts = (int) Cache::get($attemptKey, 0) + 1;
        $lockExpiresAt = now()->addSeconds(self::VERIFY_LOCK_SECONDS);

        Cache::put($attemptKey, $attempts, $lockExpiresAt);

        if ($attempts >= self::VERIFY_MAX_ATTEMPTS) {
            Cache::put($lockKey, $lockExpiresAt->timestamp, $lockExpiresAt);
            Cache::forget($attemptKey);

            return 0;
        }

        return max(0, self::VERIFY_MAX_ATTEMPTS - $attempts);
    }

    private function clearVerifyAttemptState(string $phoneNumber): void
    {
        Cache::forget($this->verifyAttemptKey($phoneNumber));
        Cache::forget($this->verifyLockKey($phoneNumber));
    }

    private function resolveTelegramUser(array $claims): User
    {
        $telegramId = $this->extractTelegramId($claims);
        $phone = preg_replace('/\D+/', '', (string) ($claims['phone_number'] ?? ''));
        $username = $claims['preferred_username'] ?? null;
        $photo = $claims['picture'] ?? null;
        [$firstName, $lastName] = $this->splitTelegramName((string) ($claims['name'] ?? ''));

        if ($telegramId === '') {
            throw new \RuntimeException('Telegram foydalanuvchi identifikatori topilmadi.');
        }

        $user = User::where('telegram_id', $telegramId)
            ->where('isDeleted', 'no')
            ->first();

        if (!$user && $phone !== '') {
            $user = User::where('phone_number', $phone)
                ->where('isDeleted', 'no')
                ->first();
        }

        if (!$user && $phone === '') {
            throw new \RuntimeException('Telegram telefon raqami qaytmadi. Iltimos, Telegramda phone ruxsatini bering.');
        }

        if (!$user) {
            $user = User::create([
                'phone_number' => $phone,
                'password' => Str::random(40),
                'name' => $firstName ?: null,
                'lastname' => $lastName ?: null,
                'verifyCode' => null,
            ]);
        }

        $conflictUser = User::where('telegram_id', $telegramId)
            ->where('id', '!=', $user->id)
            ->where('isDeleted', 'no')
            ->exists();

        if ($conflictUser) {
            throw new \RuntimeException('Bu Telegram akkaunti boshqa foydalanuvchiga ulangan.');
        }

        $user->forceFill([
            'telegram_id' => $telegramId,
            'telegram_username' => $username,
            'telegram_photo' => $photo,
            'telegram_connected_at' => now(),
            'avatar' => $user->avatar ?: $photo,
            'phone_number' => $user->phone_number ?: $phone,
            'name' => $user->name ?: $firstName,
            'lastname' => $user->lastname ?: $lastName,
        ])->save();

        return $user;
    }

    private function extractTelegramId(array $claims): string
    {
        $candidates = [
            $claims['id'] ?? null,
            $claims['telegram_id'] ?? null,
            $claims['user_id'] ?? null,
            $claims['sub'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) ($candidate ?? ''));
            if ($value === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $value) === 1) {
                return $value;
            }
        }

        return trim((string) ($claims['sub'] ?? $claims['id'] ?? ''));
    }

    private function splitTelegramName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }

    private function issueUserToken(User $user, Request $request): string
    {
        $tokenResult = $user->createToken('user_token');
        $plainTextToken = $tokenResult->plainTextToken;
        $hashedToken = hash('sha256', explode('|', $plainTextToken)[1]);

        if ($request->filled('device_id')) {
            app(\App\Services\FcmRecipientService::class)->claimToken(
                'user',
                (int) $user->id,
                $request->fcm_token,
            );
            DB::table('connected_devices')->updateOrInsert(
                ['device_id' => $request->device_id, 'user_type' => 'user'],
                [
                    'user_id' => $user->id,
                    'user_type' => 'user',
                    'token' => $hashedToken,
                    'fcm_token' => $request->fcm_token,
                    'device_name' => Str::limit(trim((string) ($request->device_name ?? 'Unknown Device')), 64, ''),
                    'platform' => Str::limit(trim((string) ($request->platform ?? 'Unknown')), 64, ''),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $activeTokens = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->pluck('token')
            ->toArray();

        $user->tokens()->whereNotIn('token', $activeTokens)->delete();

        return $plainTextToken;
    }

    private function successUserResponse(User $user, string $plainTextToken)
    {
        $cartItemCount = MyCart::where('user_id', $user->id)->sum('count_item');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'name' => $user->name,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'sex' => $user->sex,
                'token' => $plainTextToken,
                'cartItemCount' => (int) $cartItemCount,
                'photo' => $user->avatar,
                'real_balance' => $user->real_balance ?? 0,
                'cashback' => $user->cashback ?? 0,
                'mainAddressID' => $user->mainAddressID ?? 0,
                'user_main_location' => $user->location?->fullAddress ?? '',
                'position' => $user->position ?? 'reader',
                'staff_role' => $user->staff_role,
                'isVerified' => (bool) $user->isVerified,
                'phoneVerified' => $user->hasVerifiedPhone(),
                'phoneVerifiedAt' => optional($user->phone_verified_at)->toIso8601String(),
                'isSupport' => (bool) $user->isSupport,
                'hasSelectedInterests' => (bool) $user->has_selected_interests,
                'role_emoji' => $user->role_emoji,
                'role_title' => $user->role_title,
                'role_place' => $user->role_place,
            ],
        ], 201);
    }

    private function isTestPhone(string $phoneNumber): bool
    {
        return in_array($phoneNumber, $this->testPhones(), true);
    }

    private function testPhones(): array
    {
        $phones = explode(',', env('KITOBCHI_TEST_AUTH_PHONES', '998331234567,998333303034'));

        return array_values(array_filter(array_map(
            static fn ($phone) => preg_replace('/\D+/', '', trim((string) $phone)),
            $phones
        )));
    }

    private function testVerifyCode(): string
    {
        return (string) env('KITOBCHI_TEST_AUTH_CODE', self::DEFAULT_TEST_CODE);
    }

    private function smsSendCooldownKey(string $phoneNumber): string
    {
        return "auth:sms:cooldown:{$phoneNumber}";
    }

    private function smsSendWindowKey(string $phoneNumber): string
    {
        return "auth:sms:window:{$phoneNumber}";
    }

    private function smsSendWindowTimerKey(string $phoneNumber): string
    {
        return "auth:sms:window_timer:{$phoneNumber}";
    }

    private function verifyAttemptKey(string $phoneNumber): string
    {
        return "auth:verify:attempts:{$phoneNumber}";
    }

    private function verifyLockKey(string $phoneNumber): string
    {
        return "auth:verify:lock:{$phoneNumber}";
    }

    private function secondsUntil(mixed $timestamp): int
    {
        if (!$timestamp) {
            return 1;
        }

        return max(1, (int) $timestamp - now()->timestamp);
    }

    // ─────────────────────────────────────────────────────────────────
    // GUEST DATA SYNC — private metod
    //
    // Verifikatsiya muvaffaqiyatli bo'lgandan keyin
    // guest_cart va guest_favorites ni merge qilamiz.
    //
    // Cart merge mantiq:
    //   Server da bor  → qty = min(server_qty + guest_qty, max_stock)
    //   Server da yo'q → yangi yaratiladi
    //
    // Favorites merge mantiq:
    //   Server da bor  → o'tkazib yuboriladi (duplicate yo'q)
    //   Server da yo'q → qo'shiladi
    // ─────────────────────────────────────────────────────────────────
    private function syncGuestData(Request $request, User $user): void
    {
        // ── Cart ──────────────────────────────────────────────────────
        $guestCart = $request->input('guest_cart', []);

        foreach ($guestCart as $raw) {
            try {
                $productId   = (int)($raw['product_id']   ?? 0);
                $productType = (string)($raw['product_type'] ?? 'book');
                $variantId   = isset($raw['variant_id']) ? (int)$raw['variant_id'] : null;
                $quantity    = max(1, (int)($raw['quantity'] ?? 1));

                if ($productId <= 0) continue;
                if (!in_array($productType, ['book', 'stationery'])) continue;

                // Max stock olish
                $maxStock = $this->getMaxStock($productId, $productType, $variantId);
                if ($maxStock <= 0) continue; // Mahsulot mavjud emas yoki stock yo'q

                // Server da mavjudmi?
                $existing = MyCart::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->where('product_type', $productType)
                    ->when(
                        $variantId,
                        fn($q) => $q->where('variant_id', $variantId),
                        fn($q) => $q->whereNull('variant_id')
                    )
                    ->first();

                if ($existing) {
                    // Merge: mavjud + guest, max stock ga cheklanadi
                    $merged = min($existing->count_item + $quantity, $maxStock);
                    if ($merged > $existing->count_item) {
                        $existing->update(['count_item' => $merged]);
                    }
                } else {
                    // Yangi qo'shamiz
                    MyCart::create([
                        'user_id'      => $user->id,
                        'product_id'   => $productId,
                        'product_type' => $productType,
                        'variant_id'   => $variantId,
                        'count_item'   => min($quantity, $maxStock),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('AuthController::syncGuestData cart error', [
                    'user_id' => $user->id,
                    'item'    => $raw ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // ── Favorites ─────────────────────────────────────────────────
        $guestFavs = $request->input('guest_favorites', []);

        foreach ($guestFavs as $raw) {
            try {
                $productId   = (int)($raw['product_id']   ?? 0);
                $productType = (string)($raw['product_type'] ?? 'book');

                if ($productId <= 0) continue;

                // Allaqachon sevimlilar da bormi?
                $alreadyFaved = FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->where('product_type', $productType)
                    ->exists();

                if ($alreadyFaved) continue;

                // Mahsulot mavjudligini tekshirish (stock shart emas)
                $exists = $productType === 'book'
                    ? Books::where('id', $productId)->where('is_hidden', 0)->exists()
                    : Stationery::where('id', $productId)->where('is_hidden', 0)->exists();

                if (!$exists) continue;

                FavouriteProducts::create([
                    'user_id'      => $user->id,
                    'product_id'   => $productId,
                    'product_type' => $productType,
                ]);
            } catch (\Throwable $e) {
                Log::error('AuthController::syncGuestData favorite error', [
                    'user_id' => $user->id,
                    'item'    => $raw ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // MAX STOCK — mahsulot mavjudligi va max miqdorini qaytaradi
    // 0 qaytarsa — mahsulot yo'q yoki stock bitgan
    // ─────────────────────────────────────────────────────────────────
    private function getMaxStock(int $productId, string $productType, ?int $variantId): int
    {
        if ($productType === 'book') {
            $book = Books::where('id', $productId)
                ->where('is_hidden', 0)
                ->where('is_approved', 1)
                ->inStock()
                ->first(['id']);

            return (int)($book?->count ?? 0);
        }

        // Stationery
        $stat = Stationery::where('id', $productId)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->inStock()
            ->first(['id']);

        if (!$stat) return 0;

        // Variant bo'lsa — variant stock
        if ($variantId) {
            $variant = $stat->variants()->where('id', $variantId)->first(['id', 'product_id']);

            return (int)($variant?->stock ?? 0);
        }

        return (int)($stat->stock ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────
    // CHECK TOKEN
    // ─────────────────────────────────────────────────────────────────
    public function checkToken(?string $token)
    {
        $user = auth('user')->user();

        if ($user) {
            if ($blocked = $this->blockedUserResponse($user)) {
                $user->tokens()->delete();
                DB::table('connected_devices')
                    ->where('user_id', $user->id)
                    ->where('user_type', 'user')
                    ->delete();
                return $blocked;
            }

            return response()->json([
                'status'  => 'success',
                'mode'    => 'user',
                'message' => 'Token aktual',
            ], 201);
        }

        if ($token == "null" && $user == null) {
            return response()->json([
                'status'  => 'success',
                'mode'    => 'guest',
                'message' => 'Guest mode',
            ], 201);
        }

        return response()->json([
            'status'  => 'error',
            'mode'    => 'unauthorized',
            'message' => 'Token aktual emas!',
        ], 401);
    }

    private function blockedUserResponse(User $user): ?JsonResponse
    {
        if (!$user->isBlocked()) {
            return null;
        }

        $message = "Sizning akkauntingiz bloklangan.";
        if ($user->blocked_until) {
            $message .= ' Blok muddati: ' . $user->blocked_until->format('d.m.Y H:i');
        } else {
            $message .= ' Blok muddati: abadiy.';
        }

        return response()->json([
            'status' => 'error',
            'error_code' => 'user_account_blocked',
            'message' => $message,
            'blocked_until' => optional($user->blocked_until)?->toIso8601String(),
            'block_reason' => $user->block_reason,
        ], 423);
    }
}
