<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\User;
use App\Models\Order;
use App\Models\Sold;
use App\Models\MyCart;
use App\Models\UserCard;
use App\Models\BookClubNotification;
use App\Models\Conversation;
use App\Models\Books;
use App\Models\Stationery;          // ← qo'shildi
use App\Models\FavouriteProducts;
use App\Models\FcmNotifications;
use App\Models\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    // ════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Format a product (book or stationery) for API response.
     */
    private function formatProduct($product, $user = null, $type = 'book')
    {
        $isBook = $type === 'book';

        return [
            'id'            => $product->id,
            'type'          => $isBook ? 'book' : 'stationery',
            'name'          => $product->name,
            'author'        => $isBook ? ($product->author ?? null) : null,
            'material'      => $isBook ? null : ($product->material ?? null),
            'category_id'   => $product->category_id,
            'images'        => $product->images ?? [],
            'description'   => $product->description ?? null,
            'price'         => $product->price,
            'discountPrice' => $isBook
                ? ($product->discountPrice ?? $product->price)
                : ($product->discount_price ?? $product->price),
            'count'         => $isBook ? $product->count : $product->stock,
            'sales'         => $product->totalSales ?? 0,
            'weekly_sales'  => $product->totalSalesWeek ?? 0,
            'lang'          => $isBook ? ($product->lang ?? "O'zbek") : null,
            'langType'      => $isBook ? ($product->langType ?? '') : null,
            'coverType'     => $isBook ? ($product->coverType ?? 'Yumshoq') : null,
            'year'          => $isBook ? ($product->year ?? now()->year) : null,
            'favourite'     => $user
                ? FavouriteProducts::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->where('product_type', $isBook ? 'book' : 'stationery')
                    ->exists()
                : false,
            'category'      => $product->category?->title ?? null,
            'tags'          => $product->relationLoaded('tags')
                ? $product->tags->map(fn($tag) => [
                    'uz' => $tag->tag_name_uz ?? $tag->name_uz ?? null,
                    'ru' => $tag->tag_name_ru ?? $tag->name_ru ?? null,
                    'en' => $tag->tag_name_en ?? $tag->name_en ?? null,
                ])->filter()->values()
                : [],
            'seller'        => [
                'seller_id'  => $product->seller?->id,
                'shop_name'  => $product->seller?->shop_name,
                'photo'      => $product->seller?->photo,
                'isVerified' => $product->seller?->isVerified,
            ],
            'variants'      => !$isBook && $product->relationLoaded('variants')
                ? $product->variants->map(fn($v) => [
                    'id'         => $v->id,
                    'color_name' => $v->color_name,
                    'image'      => $v->image_path ?? null,
                    'stock'      => $v->stock,
                ])
                : null,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // USER — asosiy ma'lumotlar
    // ════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $user = Auth::guard('user')->user();
        $user->top_list = User::where('balance', '>', $user->balance)->count() + 1;
        return response()->json(['status' => 'success', 'data' => [$user]], 201);
    }

    public function notifications(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }
        $data = FcmNotifications::where('who', $user->id)
            ->orWhere('who', 'users')
            ->orderBy('updated_at', 'DESC')
            ->get();
        return response()->json(['status' => 'success', 'data' => $data], 201);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }

        $notification = FcmNotifications::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('who', $user->id)->orWhere('who', 'users');
            })->first();

        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Bildirishnoma topilmadi!'], 404);
        }

        $notification->update(['is_read' => true]);
        return response()->json(['status' => 'success', 'message' => "Bildirishnoma o'qilgan deb belgilandi."], 200);
    }

    public function updateFcm(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Sessiya muddati tugagan!"], 401);
        }

        $updated = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->where('device_id', $request->device_id)
            ->update(['fcm_token' => $request->fcm_token, 'updated_at' => now()]);

        return $updated
            ? response()->json(['status' => 'success', 'message' => "Bildirishnoma manzili yangilandi"], 200)
            : response()->json(['status' => 'error', 'message' => "Qurilma topilmadi!"], 404);
    }

    public function settings(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }

        if ($request->filled('name'))     $user->name     = $request->name;
        if ($request->filled('lastname')) $user->lastname = $request->lastname;
        if ($request->has('sex'))         $user->sex      = $request->sex;

        if ($request->has('bio')) {
            $user->bio = $request->filled('bio')
                ? mb_substr(strip_tags($request->bio), 0, 120)
                : null;
        }

        $presetId = $request->input('role_preset_id');
        if ($presetId !== null) {
            if ($presetId == -77) {
                $user->role_preset_id = -77;
                $user->role_emoji     = null;
                $user->role_title     = $request->filled('role_title')
                    ? mb_substr($request->role_title, 0, 100) : null;
                $user->role_place     = $request->filled('role_place')
                    ? mb_substr($request->role_place, 0, 150) : null;
            } else {
                $preset = \App\Models\RolePreset::find($presetId);
                if ($preset) {
                    $lang  = $user->locale ?? 'uz';
                    $field = match ($lang) {
                        'ru'    => 'title_ru',
                        'en'    => 'title_en',
                        'ja'    => 'title_ja',
                        default => 'title_uz',
                    };
                    $user->role_preset_id = $preset->id;
                    $user->role_emoji     = $preset->emoji;
                    $user->role_title     = $preset->$field;
                    $user->role_place     = $preset->needs_place && $request->filled('role_place')
                        ? mb_substr($request->role_place, 0, 150) : null;
                }
            }
        }

        $user->save();

        $displayRole = null;
        if ($user->role_title || $user->role_emoji) {
            $displayRole = trim(
                ($user->role_emoji ? $user->role_emoji . ' ' : '') .
                $user->role_title .
                ($user->role_place ? ' at ' . $user->role_place : '')
            );
        }

        return response()->json(['status' => 'success', 'role' => $displayRole], 200);
    }

    public function rolePresets(Request $request)
    {
        $user    = Auth::guard('user')->user();
        $presets = \App\Models\RolePreset::orderBy('sort')->get()->map(fn($r) => [
            'id'          => $r->id,
            'emoji'       => $r->emoji,
            'titles'      => ['uz' => $r->title_uz, 'ru' => $r->title_ru, 'en' => $r->title_en, 'ja' => $r->title_ja],
            'needs_place' => (bool) $r->needs_place,
            'selected'    => $user->role_preset_id == $r->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'data'    => $presets,
            'current' => [
                'preset_id' => $user->role_preset_id,
                'title'     => $user->role_title,
                'place'     => $user->role_place,
                'is_custom' => $user->role_preset_id == -77,
            ],
        ], 200);
    }

    // ════════════════════════════════════════════════════════════════════
    // LOCATIONS
    // ════════════════════════════════════════════════════════════════════

    public function my_locations(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
        $data = DB::table('locations')->where('user_id', $user->id)->where('isDeleted', false)->get();
        return response()->json(['status' => 'success', 'data' => $data], 201);
    }

    public function delete_location(Request $request, Locations $location)
    {
        $user = Auth::guard('user')->user();
        if ($user && $location->id != $user->mainAddressID) {
            $location->isDeleted = true;
            $location->update();
            return response()->json(['status' => 'success', 'message' => "Muvaffaqiyatli bajarildi."], 201);
        }
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
    }

    public function new_location(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
        $location            = new Locations();
        $location->user_id   = $user->id;
        $location->lat       = $request->lat;
        $location->lon       = $request->lon;
        $location->fullAddress = $request->fullAddress;
        $location->save();
        $user->update(['mainAddressID' => $location->id]);
        return response()->json(['status' => 'success', 'location_id' => $location->id], 201);
    }

    public function select_location(Request $request, string $id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
        $user->mainAddressID = $id;
        $user->save();
        return response()->json(['status' => 'success', 'message' => "Location set ID:" . $id], 201);
    }

    // ════════════════════════════════════════════════════════════════════
    // AVATAR
    // ════════════════════════════════════════════════════════════════════

    public function upload_avatar(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240']);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $imagePath    = $request->file('image')->store('avatar', 'public');
            $user->avatar = $imagePath;
            $user->save();
            return response()->json(['status' => 'success', 'message' => 'Image uploaded successfully.', 'image_url' => $imagePath], 201);
        }
        return response()->json(['status' => 'error', 'message' => 'Failed to upload image.'], 400);
    }

    /**
     * Sevimliga qo'shish / olib tashlash (toggle) — book + stationery.
     *
     * GET /api/favourite_products/{productId}/add?type=book|stationery
     */
    public function addFavourite(Request $request, string $productId)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // type parametridan aniqlash, default: 'book'
        $type = in_array($request->query('type'), ['book', 'stationery'])
            ? $request->query('type')
            : 'book';

        // Mahsulot mavjudligini tekshirish
        $variantId = null;

        if ($type === 'stationery') {
            $product = Stationery::with('variants')->find($productId);
            if (!$product) {
                return response()->json(['status' => 'error', 'message' => 'Mahsulot topilmadi'], 404);
            }

            // Variant_id request'dan kelgan bo'lsa — ishlatamiz,
            // aks holda — birinchi mavjud (stock > 0) variantni olamiz
            if ($request->filled('variant_id')) {
                $variantId = (int) $request->variant_id;
            } else {
                $firstVariant = $product->variants
                    ->where('stock', '>', 0)
                    ->sortBy('id')
                    ->first();

                // Agar hamma variant tugagan bo'lsa — birinchi variantni olamiz
                if (!$firstVariant) {
                    $firstVariant = $product->variants->sortBy('id')->first();
                }

                $variantId = $firstVariant?->id;
            }
        } else {
            $product = Books::find($productId);
            if (!$product) {
                return response()->json(['status' => 'error', 'message' => 'Mahsulot topilmadi'], 404);
            }
        }

        // Toggle: agar allaqachon bor bo'lsa — o'chirish
        $existing = FavouriteProducts::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('product_type', $type)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'status'  => 'success',
                'action'  => 'removed',
                'message' => 'Sevimlilardan olib tashlandi',
            ], 200);
        }

        // Yangi qo'shish
        FavouriteProducts::create([
            'user_id'      => $user->id,
            'product_id'   => $productId,
            'product_type' => $type,
            'variant_id'   => $variantId,   // book uchun null, stationery uchun variant id
        ]);

        return response()->json([
            'status'     => 'success',
            'action'     => 'added',
            'message'    => "Sevimlilarga qo'shildi",
            'variant_id' => $variantId,
        ], 200);
    }

// ──────────────────────────────────────────────
// 2. favouriteProducts — variant ma'lumotlari to'g'ri qaytarilsin
//    va formatProduct() ga variant uzatilsin
// ──────────────────────────────────────────────

    /**
     * Sevimlillar ro'yxati — book va stationery birgalikda.
     *
     * GET /api/favourite_products
     */
    public function favouriteProducts(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $favourites = FavouriteProducts::where('user_id', $user->id)
            ->with([
                'product.seller',
                'variant',           // tanlangan variant (morphTo yoki belongsTo)
            ])
            ->latest()
            ->paginate(20);

        $result = $favourites->getCollection()
            ->map(function ($item) use ($user) {
        $product = $item->product;

        if (!$product) {
            return null;
        }

        return [
            'cart_id' => $item->id,
            'quantity' => $item->count_item,
            'product' => $this->formatProduct($product, $user, $item->product_type, $item->variant),
            'variant' => $item->variant
      ? [
          'id' => $item->variant->id,
          'color_name' => $item->variant->color_name,
          'image' => $item->variant->image_path,
          'stock' => $item->variant->stock,
        ]
      : null,
        ];
    })->filter()->values();

        $favourites->setCollection(collect($result));

        return response()->json([
            'status'     => 'success',
            'data'       => $result,
            'pagination' => [
                'current_page' => $favourites->currentPage(),
                'per_page'     => $favourites->perPage(),
                'total'        => $favourites->total(),
            ],
        ], 200);
    }

    /**
     * Barcha sevimlilarni tozalash.
     *
     * DELETE /api/favourite_products/clear
     * yoki  DELETE /api/favourite_products/clear?type=book|stationery  (faqat bir tur)
     */
    public function clearFavorites(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $query = FavouriteProducts::where('user_id', $user->id);

        // Ixtiyoriy: faqat ma'lum turni tozalash
        $type = $request->query('type');
        if (in_array($type, ['book', 'stationery'])) {
            $query->where('product_type', $type);
        }

        $deleted = $query->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sevimlilar tozalandi',
            'deleted' => $deleted,
        ], 200);
    }

    // ════════════════════════════════════════════════════════════════════
    // MISC
    // ════════════════════════════════════════════════════════════════════

    public function getCashbackCount(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }
        return response()->json(['status' => 'success', 'cashback' => $user->cashback], 201);
    }

    public function updateStatus(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }
        if ($request->has('session_duration')) {
            $user->increment('total_seconds_spent', $request->session_duration);
        }
        $user->update(['last_seen_at' => now()]);
        return response()->json(['status' => 'success']);
    }

    public function updateLocale(Request $request, $locale)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }
        $user && $user->locale != $locale ? $user->update(['locale' => $locale]) : null;
        return response()->json(['status' => 'success']);
    }

    public function devices(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $devices = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->select('id', 'device_id', 'device_name', 'platform', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status'            => 'success',
            'current_device_id' => $request->header('X-Device-Id'),
            'data'              => $devices,
        ], 200);
    }

    public function logoutDevice(Request $request, $id)
    {
        $user   = $request->user();
        $device = DB::table('connected_devices')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Qurilma topilmadi'], 404);
        }

        $user->tokens()->where('token', $device->token)->delete();
        DB::table('connected_devices')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => "Qurilma muvaffaqiyatli o'chirildi"], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success']);
    }

    // ════════════════════════════════════════════════════════════════════
    // GLOBAL COUNTS
    // ════════════════════════════════════════════════════════════════════

    public function getGlobalCounts(Request $request)
    {
        $user                = Auth::guard('user')->user();
        $packagingPrice = 25000;
        $personalUnread      = 0;
        $shopUnread          = 0;
        $unreadNotifications = 0;
        $cartItems           = 0;
        $orderCount          = 0;
        $favouriteCount      = 0;
        $selectedLocation    = false;
        $cards               = 0;
        $giftCertsCount      = 0;
        $giftCertsTotal      = 0;
        $mysteryBoxData      = null;

        if ($user) {
            $personalUnread = Conversation::where('type', 'personal')
                ->where(fn($q) => $q->where('user_id', $user->id)->orWhere('receiver_id', $user->id))
                ->whereHas('messages', fn($q) => $q->where('is_read', 0)->where('sender_id', '!=', $user->id))
                ->withCount(['messages as unread_messages' => fn($q) =>
                    $q->where('is_read', 0)->where('sender_id', '!=', $user->id)])
                ->get()->sum('unread_messages');

            $shopUnread = Conversation::where('type', 'shop')
                ->where('user_id', $user->id)
                ->whereHas('messages', fn($q) => $q->where('is_read', 0)->where('sender_id', '!=', $user->id))
                ->withCount(['messages as unread_messages' => fn($q) =>
                    $q->where('is_read', 0)->where('sender_id', '!=', $user->id)])
                ->get()->sum('unread_messages');

            $unreadNotifications = BookClubNotification::where('user_id', $user->id)
                ->where('is_read', false)->count();

            $cartItems      = MyCart::where('user_id', $user->id)->sum('count_item');
            $orderCount     = Sold::where('status', '!=', 'F')->where('user_id', $user->id)->count();
            $favouriteCount = FavouriteProducts::where('user_id', $user->id)->count();
            $selectedLocation = Locations::where('user_id', $user->id)
                ->where('id', $user->mainAddressID)->where('isDeleted', false)->exists();
            $cards = $user->cards()->count();

            $certsQuery     = \App\Models\GiftCertificate::where('recipient_user_id', $user->id)
                ->where('status', \App\Models\GiftCertificate::STATUS_ACTIVE)
                ->where('nominal_uzs', '>', 0)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
            $giftCertsCount = $certsQuery->count();
            $giftCertsTotal = (int) $certsQuery->sum('nominal_uzs');

            $sub = \App\Models\MysteryBoxSubscription::where('user_id', $user->id)
                ->whereIn('status', [
                    'active','paused',
                ])
                ->with('plan:id,name_uz,books_per_month')
                ->orderByRaw("FIELD(status, 'active', 'paused', 'pending_payment')")
                ->first();

            if ($sub) {
                $addr = $sub->address;
                $mysteryBoxData = [
                    'subscription_id'  => $sub->id,
                    'status'           => $sub->status,
                    'plan_name'        => $sub->plan?->name_uz ?? '',
                    'total_months'     => (int) $sub->total_months,
                    'delivered_months' => (int) $sub->delivered_months,
                    'books_per_month'  => (int) $sub->books_per_month,
                    'next_delivery_at' => $sub->next_delivery_at?->format('d.m.Y'),
                    'ends_at'          => $sub->ends_at?->format('d.m.Y'),
                    'has_address'      => !empty($addr['fullAddress'] ?? null),
                ];
            }
        }
        $bookItemCount  = MyCart::where('user_id', $user->id)->where('product_type', 'book')->sum('count_item');
    $packagingPrice = $bookItemCount >= 4 ? 40000 : 25000;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'unread_notifications'     => (int) $unreadNotifications,
                'unread_personal_messages' => (int) $personalUnread,
                'unread_shop_messages'     => (int) $shopUnread,
                'cart_items'               => (int) $cartItems,
                'pending_orders'           => (int) $orderCount,
                'favourites_count'         => (int) $favouriteCount,
                'selected_location'        => (bool) $selectedLocation,
                'cards'                    => (int) $cards,
                'gift_certs_count'         => (int) $giftCertsCount,
                'gift_certs_total'         => (int) $giftCertsTotal,
                'mystery_box'              => $mysteryBoxData,
                'isVerified'               => $user ? (bool) $user->isVerified : false,
                'isSupport'                => $user ? (bool) $user->isSupport  : false,
                'onPremium'                => false,
                'onReels'                  => false,
                'ramadan'                  => false,
                'data_required'            => $user ? (bool) $user->firstEdit  : false,
                'stopSales'                => false,
                'packaging_price'           => $packagingPrice,
            ],
        ]);
    }
}