<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\User;
use App\Models\Order;
use App\Models\Sold;
use App\Models\MyCart;
use App\Models\UserCard;
use App\Models\BookClubNotification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Books;
use App\Models\Stationery;
use App\Models\FavouriteProducts;
use App\Models\BookCategories;
use App\Models\UserInterestSelection;
use App\Models\FcmNotifications;
use App\Models\DeliveryService;
use App\Models\ProjectSetting;
use App\Services\DeliveryZoneResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Support\ProductImageUrls;
use App\Support\ProductPayloadFormatter;

class UserController extends Controller
{
    private const ACTIVE_HOME_ORDER_LIMIT = 4;

    public function __construct(
        private readonly DeliveryZoneResolverService $deliveryZoneResolverService,
    ) {}

    private function isUzbekistanAddress(?string $address): bool
    {
        $normalized = mb_strtolower(trim((string) $address));

        if ($normalized === '') {
            return false;
        }

        return str_contains($normalized, 'uzbekiston')
            || str_contains($normalized, 'uzbekistan')
            || str_contains($normalized, 'узбекистан');
    }

    private function isWithinUzbekistanBounds(float $lat, float $lon): bool
    {
        return $lat >= 37.0
            && $lat <= 45.7
            && $lon >= 55.9
            && $lon <= 73.3;
    }

    // ════════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Format a product (book or stationery) for API response.
     */
    private function formatProduct($product, $user = null, $type = 'book')
    {
        return ProductPayloadFormatter::format($product, [
            'user' => $user,
            'type' => $type,
            'category_format' => 'title',
        ]);
    }

    private function activeOrdersBaseQuery(User $user)
    {
        return Sold::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where(function ($query) {
                $query->whereIn('status_code', [
                    OrderStatusCode::PENDING->value,
                    OrderStatusCode::PACKING->value,
                    OrderStatusCode::IN_DELIVERY->value,
                    OrderStatusCode::DELIVERED->value,
                ])->orWhere(function ($fallback) {
                    $fallback->whereNull('status_code')
                        ->whereIn('status', [
                            OrderStatusCode::PENDING->legacy(),
                            OrderStatusCode::PACKING->legacy(),
                            OrderStatusCode::IN_DELIVERY->legacy(),
                            OrderStatusCode::DELIVERED->legacy(),
                        ]);
                });
            })
            ->where(function ($query) {
                $query->whereNotIn('status_code', [
                    OrderStatusCode::CANCELLED->value,
                    OrderStatusCode::RETURNED->value,
                    OrderStatusCode::CUSTOMER_RECEIVED->value,
                ])->orWhere(function ($fallback) {
                    $fallback->whereNull('status_code')
                        ->whereNotIn('status', [
                            OrderStatusCode::CANCELLED->legacy(),
                            OrderStatusCode::RETURNED->legacy(),
                            OrderStatusCode::CUSTOMER_RECEIVED->legacy(),
                        ]);
                });
            });
    }

    private function orderStatusLabelForHome(Sold $order): string
    {
        $paymentCode = $order->payment_status_code;
        $statusCode = $order->status_code;

        if ($paymentCode === PaymentStatusCode::CARD_PENDING->value) {
            return "To'lov kutilmoqda";
        }

        return match ($statusCode) {
            OrderStatusCode::PENDING->value => 'Kutilmoqda',
            OrderStatusCode::PACKING->value => "Qadoqlanmoqda",
            OrderStatusCode::IN_DELIVERY->value => "Yo'lda",
            OrderStatusCode::DELIVERED->value => "Yetib bordi",
            OrderStatusCode::CUSTOMER_RECEIVED->value => "Mijoz qabul qildi",
            default => 'Jarayonda',
        };
    }

    private function formatHomeOrderPreview(Sold $order): array
    {
        $expectedDeliveryAt = $order->estimatedDeliveryAt();
        $items = collect($order->items ?? [])->map(function ($item) {
            return [
                'name' => $item['name'] ?? null,
                'cover' => $item['cover'] ?? null,
                'type' => $item['type'] ?? null,
                'count_item' => (int) ($item['count_item'] ?? 0),
            ];
        })->values()->all();

        $address = collect($order->address ?? [])->map(function ($addr) {
            if (!is_array($addr)) {
                return null;
            }

            return [
                'fullAddress' => $addr['fullAddress'] ?? $addr['branch_address'] ?? null,
                'fullName' => $addr['fullName'] ?? $addr['contact_name'] ?? null,
                'phoneNumber' => $addr['phoneNumber'] ?? $addr['phone_number'] ?? null,
                'lat' => $addr['lat'] ?? null,
                'lon' => $addr['lon'] ?? null,
            ];
        })->filter()->values()->all();

        return [
            'id' => (int) $order->id,
            'status' => $order->status,
            'status_code' => $order->status_code,
            'paymentStatus' => (int) $order->paymentStatus,
            'payment_status_code' => $order->payment_status_code,
            'status_label' => $this->orderStatusLabelForHome($order),
            'amount' => (int) ($order->amount ?? 0),
            'deliveryType' => $order->deliveryType,
            'created_at' => $order->created_at?->toIso8601String(),
            'formatted_created_at' => optional($order->created_at)?->format('d.m.Y HH:mm'),
            'expected_delivery_at' => $expectedDeliveryAt?->toIso8601String(),
            'is_delivery_delayed' => $order->isDeliveryDelayed(),
            'address' => $address,
            'recipient_region' => $order->recipient_region,
            'recipient_address' => $order->recipient_address,
            'items' => $items,
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
        $data = FcmNotifications::query()
            ->where(function ($query) use ($user) {
                $query->where('who', (string) $user->id)
                    ->orWhere('who', 'users');
            })
            ->orderBy('updated_at', 'DESC')
            ->get();

        if (Schema::hasTable('fcm_notification_reads')) {
            $readIds = DB::table('fcm_notification_reads')
                ->where('user_id', $user->id)
                ->whereIn('notification_id', $data->pluck('id'))
                ->pluck('notification_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $data->each(function (FcmNotifications $notification) use ($readIds) {
                $notification->is_read = in_array((int) $notification->id, $readIds, true);
            });
        }

        $locale = $this->normalizeNotificationLocale($user->locale ?? null);
        $data->each(function (FcmNotifications $notification) use ($locale) {
            $title = $notification->{"name_{$locale}"} ?? null;
            $body = $notification->{"description_{$locale}"} ?? null;

            if (filled($title)) {
                $notification->name = $title;
            }
            if (filled($body)) {
                $notification->description = $body;
            }
        });

        return response()->json(['status' => 'success', 'data' => $data], 201);
    }

    private function normalizeNotificationLocale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';
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

        if (Schema::hasTable('fcm_notification_reads')) {
            DB::table('fcm_notification_reads')->updateOrInsert(
                ['notification_id' => $notification->id, 'user_id' => $user->id],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );
        } elseif ((string) $notification->who === (string) $user->id) {
            $notification->update(['is_read' => true]);
        }

        return response()->json(['status' => 'success', 'message' => "Bildirishnoma o'qilgan deb belgilandi."], 200);
    }

    public function updateFcm(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
        ]);

        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Sessiya muddati tugagan!"], 401);
        }

        $deviceName = Str::limit(trim((string) $request->input('device_name', '')), 64, '');
        $platform = Str::limit(trim((string) $request->input('platform', '')), 64, '');
        $now = now();

        app(\App\Services\FcmRecipientService::class)->claimToken(
            'user',
            (int) $user->id,
            $request->fcm_token,
        );

        $query = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->where('device_id', $request->device_id);

        $payload = [
            'fcm_token' => $request->fcm_token,
            'token' => $user->currentAccessToken()?->token,
            'updated_at' => $now,
        ];

        if ($deviceName !== '') {
            $payload['device_name'] = $deviceName;
        }

        if ($platform !== '') {
            $payload['platform'] = $platform;
        }

        if ($query->exists()) {
            $query->update($payload);
        } else {
            DB::table('connected_devices')->insert([
                'user_id' => $user->id,
                'user_type' => 'user',
                'device_id' => $request->device_id,
                'device_name' => $deviceName !== '' ? $deviceName : 'Unknown Device',
                'platform' => $platform !== '' ? $platform : 'unknown',
                'fcm_token' => $request->fcm_token,
                'token' => $user->currentAccessToken()?->token,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json(['status' => 'success', 'message' => "Bildirishnoma manzili yangilandi"], 200);
    }

    public function settings(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|min:3|max:32|regex:/^[A-Za-z0-9_.]+$/|unique:users,username,' . $user->id,
            // MUHIM: piyola'dagi "Ma'lumotlarim" tahrirlash formasi bilan
            // funksional parallellik uchun qo'shildi — Kitobchi web
            // frontendida (profile/info.vue, profile/edit.vue) bu ikki
            // maydon oldin UMUMAN ko'rsatilmas/yuborilmas edi.
            'email'     => 'nullable|email:rfc|max:40|unique:users,email,' . $user->id,
            'birthdate' => 'nullable|date|before:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->filled('name'))     $user->name     = $request->name;
        if ($request->filled('lastname')) $user->lastname = $request->lastname;
        if ($request->has('sex'))         $user->sex      = $request->sex;
        if ($request->has('email'))       $user->email     = $request->filled('email') ? $request->email : null;
        if ($request->has('birthdate'))   $user->birthdate = $request->filled('birthdate') ? $request->birthdate : null;
        if ($request->has('username')) {
            $username = mb_strtolower(trim((string) $request->username));
            $username = ltrim($username, '@');
            $user->username = $username !== '' ? $username : null;
        }

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

        return response()->json([
            'status' => 'success',
            'role' => $displayRole,
            'username' => $user->username,
            // MUHIM: frontend (auth.ts store) haqiqiy saqlangan qiymatlarni
            // shu javobdan o'qib, lokal `user` cookie'sini yangilaydi —
            // birthdate/email uchun ham xuddi shunday (pastga qarang).
            'email' => $user->email,
            'birthdate' => $user->birthdate,
        ], 200);
    }

    public function byUsername(string $username)
    {
        $normalized = mb_strtolower(trim(ltrim($username, '@')));
        if ($normalized === '') {
            return response()->json(['status' => 'error', 'message' => 'Username topilmadi'], 404);
        }

        // `username` ustuni bazada bo'lmasa (hozircha yo'q) — 500 emas, oddiy "topilmadi"
        if (! Schema::hasColumn('users', 'username')) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $user = User::query()
            ->select('id', 'name', 'lastname', 'username', 'avatar', 'position', 'staff_role', 'isVerified', 'isSupport', 'role_emoji', 'role_title', 'role_place')
            ->where('username', $normalized)
            ->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
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
        // MUHIM: web endi manzilni GPS/geolocation orqali emas, Viloyat →
        // Tuman → Mahalla/qishloq (MIMAXUZ/uzbekistan-regions-data)
        // tanlovi orqali oladi — shu sababli `lat`/`lon` endi MAJBURIY
        // emas (nullable). Mobil ilova/kuryer oqimlari hali ham haqiqiy
        // GPS koordinatasini yuborishi mumkin — shu maydonlar shunchaki
        // ixtiyoriy bo'lib qoldi, olib tashlanmadi.
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric'],
            'lon' => ['nullable', 'numeric'],
            'fullAddress' => ['required', 'string', 'max:1000'],
            'countryCode' => ['nullable', 'string', 'max:8'],
            'regionSlug' => ['nullable', 'string', 'max:100'],
            'regionName' => ['nullable', 'string', 'max:150'],
            'districtName' => ['nullable', 'string', 'max:150'],
            'cityName' => ['nullable', 'string', 'max:150'],
        ]);

        $lat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $lon = isset($validated['lon']) ? (float) $validated['lon'] : null;
        $fullAddress = trim((string) $validated['fullAddress']);
        $countryCode = strtoupper((string) ($validated['countryCode'] ?? ''));
        $isWithinUzbekistan = $countryCode === 'UZ'
            || $this->isUzbekistanAddress($fullAddress)
            || (!empty($validated['regionName']))
            || ($lat !== null && $lon !== null && $this->isWithinUzbekistanBounds($lat, $lon));

        $isSupportedCountry = $countryCode !== ''
            && $this->deliveryZoneResolverService->isCountrySupported($countryCode);

        if (!$isWithinUzbekistan && !$isSupportedCountry) {
            return response()->json([
                'status' => 'error',
                'message' => "Hozircha bu hudud uchun logistika hali yoqilmagan.",
            ], 422);
        }

        $location            = new Locations();
        $location->user_id   = $user->id;
        $location->lat       = $lat;
        $location->lon       = $lon;
        $location->fullAddress = $fullAddress;
        $location->country_code = $countryCode !== '' ? $countryCode : ($isWithinUzbekistan ? 'UZ' : null);
        $location->region_slug = $validated['regionSlug'] ?? null;
        $location->region_name = $validated['regionName'] ?? null;
        $location->district_name = $validated['districtName'] ?? null;
        $location->city_name = $validated['cityName'] ?? null;
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

    /**
     * Onboarding'dagi "Sizga nima yoqadi?" chip-tanlash qadamidan
     * qiziqishlarni saqlaydi. Bo'sh massiv ham qabul qilinadi (foydalanuvchi
     * "o'tkazib yuborish"ni bosgan) — ikkalasida ham `has_selected_interests`
     * true bo'ladi, shu orqali bu qadam boshqa hech qachon qayta
     * ko'rsatilmaydi.
     *
     * Eski tanlovlar to'liq almashtiriladi (qo'shilmaydi) — bu ekran hozircha
     * faqat BIR MARTA (onboardingda) chaqiriladi, shuning uchun sync
     * xulq-atvori kutilganidek ishlaydi.
     */
    public function saveInterests(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'category_ids' => ['present', 'array'],
            'category_ids.*' => ['integer', 'exists:book_categories,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $categoryIds = array_values(array_unique(array_map('intval', $request->input('category_ids', []))));

        DB::transaction(function () use ($user, $categoryIds) {
            UserInterestSelection::where('user_id', $user->id)->delete();

            if (! empty($categoryIds)) {
                $rows = array_map(fn (int $categoryId) => [
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $categoryIds);
                UserInterestSelection::insert($rows);
            }

            $user->has_selected_interests = true;
            $user->save();
        });

        // Reading Intelligence did-vektori endi bu tanlovlarga ham
        // bog'liq — eski keshni darhol tozalaymiz (1 soat kutmasdan).
        Cache::forget("reading-intel:interests:{$user->id}");

        return response()->json(['status' => 'success']);
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
                'product.category',  // N+1 oldini oladi (formatCategory uchun)
                'variant',           // tanlangan variant (morphTo yoki belongsTo)
            ])
            ->latest()
            ->paginate(20);

        // Stock accessorlari uchun jami qoldiqni bitta so'rovda iliqlaymiz
        $branchStock = app(\App\Services\BranchStockService::class);
        $branchStock->warmProducts($favourites->getCollection()->map(fn ($i) => $i->product)->filter());
        $branchStock->warmVariants($favourites->getCollection()->map(fn ($i) => $i->variant)->filter());

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
          'image' => ProductImageUrls::originalUrl($item->variant->image_path),
          'image_url' => ProductImageUrls::originalUrl($item->variant->image_path),
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

        $deviceId = trim((string) $request->header('X-Device-Id', ''));
        if ($deviceId !== '' && $deviceId !== 'unknown_device') {
            DB::table('connected_devices')
                ->where('user_id', $user->id)
                ->where('user_type', 'user')
                ->where('device_id', $deviceId)
                ->update(['updated_at' => now()]);
        }

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
            ->selectRaw('id, device_id, device_name, platform, COALESCE(updated_at, created_at) as updated_at')
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
            ->where('user_type', 'user')
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
        $user   = Auth::guard('user')->user();
        if ($user && method_exists($user, 'isBlocked') && $user->isBlocked()) {
            $user->tokens()->delete();
            DB::table('connected_devices')
                ->where('user_id', $user->id)
                ->where('user_type', 'user')
                ->delete();

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
        $cfg    = Cache::remember('project_settings', 300, fn() => ProjectSetting::first());

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
        $activeOrders        = [];

        $bookItemCount = 0;

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
            $activeOrdersQuery = $this->activeOrdersBaseQuery($user);
            $orderCount = (clone $activeOrdersQuery)->count();
            $activeOrders = (clone $activeOrdersQuery)
                ->latest()
                ->limit(self::ACTIVE_HOME_ORDER_LIMIT)
                ->get()
                ->map(fn (Sold $order) => $this->formatHomeOrderPreview($order))
                ->values()
                ->all();
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

            try {
                $sub = \App\Models\MysteryBoxSubscription::where('user_id', $user->id)
                    ->whereIn('status', [
                        'active', 'paused',
                    ])
                    ->with('plan:id,name_uz,books_per_month')
                    ->orderByRaw("FIELD(status, 'active', 'paused', 'pending_payment')")
                    ->first();

                if ($sub) {
                    $addr = is_array($sub->address) ? $sub->address : [];
                    $mysteryBoxData = [
                        'subscription_id'  => (int) $sub->id,
                        'status'           => $sub->status,
                        'plan_name'        => $sub->plan?->name_uz ?? '',
                        'total_months'     => (int) ($sub->total_months ?? 0),
                        'delivered_months' => (int) ($sub->delivered_months ?? 0),
                        'books_per_month'  => (int) ($sub->books_per_month ?? 0),
                        'next_delivery_at' => optional($sub->next_delivery_at)?->format('d.m.Y'),
                        'ends_at'          => optional($sub->ends_at)?->format('d.m.Y'),
                        'has_address'      => !empty($addr['fullAddress'] ?? null),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Global counts mystery box block failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $mysteryBoxData = null;
            }

            $bookItemCount = MyCart::where('user_id', $user->id)
                ->where('product_type', 'book')
                ->sum('count_item');
        }

        $threshold      = (int) ($cfg?->packaging_threshold   ?? 4);
        $priceSmall     = (int) ($cfg?->packaging_price_small ?? 25000);
        $priceLarge     = (int) ($cfg?->packaging_price_large ?? 40000);
        $packagingPrice = $bookItemCount >= $threshold ? $priceLarge : $priceSmall;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'unread_notifications'     => (int) $unreadNotifications,
                'unread_personal_messages' => (int) $personalUnread,
                'unread_shop_messages'     => (int) $shopUnread,
                'cart_items'               => (int) $cartItems,
                'pending_orders'           => (int) $orderCount,
                'active_orders'            => $activeOrders,
                'favourites_count'         => (int) $favouriteCount,
                'selected_location'        => (bool) $selectedLocation,
                'cards'                    => (int) $cards,
                'gift_certs_count'         => (int) $giftCertsCount,
                'gift_certs_total'         => (int) $giftCertsTotal,
                'mystery_box'              => $mysteryBoxData,
                'isVerified'               => $user ? (bool) $user->isVerified : false,
                'isSupport'                => $user ? (bool) $user->isSupport  : false,
                'position'                 => $user?->position ?? 'reader',
                'staff_role'               => $user?->staff_role,
                'username'                 => $user?->username,
                'role_emoji'               => $user?->role_emoji,
                'role_title'               => $user?->role_title,
                'role_place'               => $user?->role_place,
                'reputation_score'         => $user ? round((float) ($user->reputation_score ?? 82), 2) : 82.0,
                'cash_on_delivery_allowed' => $user ? (bool) ($user->cash_on_delivery_allowed ?? true) : true,
                'cod_return_strikes'       => $user ? (int) ($user->cod_return_strikes ?? 0) : 0,
                'unread_group_messages'    => $user
                    ? (int) Conversation::query()
                        ->where('type', 'group')
                        ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                        ->get()
                        ->sum(function ($conversation) use ($user) {
                            $participant = $conversation->participants()
                                ->where('user_id', $user->id)
                                ->first();

                            return Message::query()
                                ->where('conversation_id', $conversation->id)
                                ->where('sender_id', '!=', $user->id)
                                ->where('is_deleted', 0)
                                ->when($participant?->last_read_at, fn ($q) => $q->where('created_at', '>', $participant->last_read_at))
                                ->count();
                        })
                    : 0,
                'onPremium'                => (bool) ($cfg?->on_premium  ?? false),
                'onReels'                  => (bool) ($cfg?->on_reels    ?? false),
                'ramadan'                  => (bool) ($cfg?->ramadan     ?? false),
                'data_required'            => $user ? (bool) $user->firstEdit  : false,
                'stopSales'                => (bool) ($cfg?->stop_sales  ?? false),
                'showHomeSpecialSections'  => $cfg?->show_home_special_sections === null ? true : (bool) $cfg->show_home_special_sections,
                'packaging_price'          => $packagingPrice,
            ],
        ]);
    }
}
