<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MyCart;
use App\Models\FavouriteProducts;
use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // STORE — SMS yuborish va kodni tasdiqlash
    // ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $randomString = Str::random(10);
        $phone_number = str_replace([' ', '-'], '', $request->phone_number);

        // ── 1-BOSQICH: SMS yuborish ───────────────────────────────────
        if (strlen($phone_number) == 12 && is_null($request->verifyCode)) {
            $verifyCode = ($phone_number == '998331234567')
                ? '222222'
                : rand(100001, 999999);

            if ($phone_number != '998331234567') {
                Http::post(route('api.sendSms'), [
                    'phone' => $phone_number,
                    'msg'   => "<#> Kitobchi ilovasida tasdiqlash uchun kod: $verifyCode. $randomString",
                ]);
            }

            $user = User::where('phone_number', $phone_number)
                ->where('isDeleted', 'no')
                ->first();

            if (!$user) {
                User::create([
                    'phone_number' => $phone_number,
                    'verifyCode'   => $verifyCode,
                    'password'     => bcrypt('kitobchi122'),
                ]);
            } else {
                $user->update(['verifyCode' => $verifyCode]);
            }

            return response()->json(['status' => 'success', 'message' => 'Kod yuborildi'], 201);
        }

        // ── 2-BOSQICH: Kodni tasdiqlash ───────────────────────────────
        $user = User::where('phone_number', $phone_number)
            ->where('isDeleted', 'no')
            ->first();

        if ($user && $user->verifyCode == $request->verifyCode) {

            // Token yaratish
            $tokenResult    = $user->createToken('user_token');
            $plainTextToken = $tokenResult->plainTextToken;
            $hashedToken    = hash('sha256', explode('|', $plainTextToken)[1]);

            // Qurilmani saqlash
            if ($request->has('device_id')) {
                DB::table('connected_devices')->updateOrInsert(
                    ['device_id' => $request->device_id],
                    [
                        'user_id'     => $user->id,
                        'user_type'   => 'user',
                        'token'       => $hashedToken,
                        'fcm_token'   => $request->fcm_token,
                        'device_name' => $request->device_name ?? 'Unknown Device',
                        'platform'    => $request->platform   ?? 'Unknown',
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

            $user->update(['verifyCode' => null, 'verified' => 1]);

            // ── ✅ GUEST DATA SYNC ────────────────────────────────────
            // Token yaratilgandan keyin, response dan oldin
            // guest_cart va guest_favorites ni darhol serverga biriktiramiz.
            // Alohida endpoint kerak emas — bitta requestda bajariladi.
            if ($request->has('guest_cart') || $request->has('guest_favorites')) {
                $this->syncGuestData($request, $user);
            }
            // ─────────────────────────────────────────────────────────

            // cartItemCount ni sync dan keyin qayta hisoblaymiz
            $cartItemCount = MyCart::where('user_id', $user->id)->sum('count_item');

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'                 => $user->id,
                    'phone_number'       => $user->phone_number,
                    'name'               => $user->name,
                    'lastname'           => $user->lastname,
                    'sex'                => $user->sex,
                    'token'              => $plainTextToken,
                    'cartItemCount'      => (int) $cartItemCount,
                    'photo'              => $user->avatar,
                    'real_balance'       => $user->real_balance  ?? 0,
                    'cashback'           => $user->cashback       ?? 0,
                    'mainAddressID'      => $user->mainAddressID  ?? 0,
                    'user_main_location' => $user->location->fullAddress ?? '',
                ],
            ], 201);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Tekshiruv kodi xato kiritildi',
        ], 400);
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
                ->where('count', '>', 0)
                ->value('count');

            return (int)($book ?? 0);
        }

        // Stationery
        $stat = Stationery::where('id', $productId)
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->where('stock', '>', 0)
            ->first(['id', 'stock']);

        if (!$stat) return 0;

        // Variant bo'lsa — variant stock
        if ($variantId) {
            $variantStock = $stat->variants()
                ->where('id', $variantId)
                ->where('stock', '>', 0)
                ->value('stock');

            return (int)($variantStock ?? 0);
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
}