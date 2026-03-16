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
    
    /**
     * Format a book for API response (reuse from previous updates)
     */
    private function formatBook($book, $user = null, $isFavourite = false)
    {
        return [
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'category_id' => $book->category_id,
            'images' => $book->images,
            'description' => $book->description,
            'price' => $book->price,
            'count' => $book->count,
            'lang' => $book->lang ?? 'O\'zbek',
            'langType' => $book->langType ?? '',
            'coverType' => $book->coverType ?? 'Yumshoq',
            'year' => $book->year ?? now()->year,
            'discountPrice' => $book->discountPrice,
            'favourite' => $isFavourite,
            'category' => $book->category ? $book->category->title : null,
            'tags' => $book->tags->map(function ($tag) {
                return [
                    'uz' => $tag->tag_name_uz,
                    'ru' => $tag->tag_name_ru,
                    'en' => $tag->tag_name_en,
                ];
            }),
            'seller' => [
                'seller_id' => $book->seller ? $book->seller->id : null,
                'shop_name' => $book->seller ? $book->seller->shop_name : null,
                'photo' => $book->seller ? $book->seller->photo : null,
            ],
        ];
    }

    /**
     * Shaxsiy ma'lumotlarni chiqarish
     */
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
        $data = FcmNotifications::where('who', $user->id)->orWhere('who', 'users')->orderBy('updated_at', 'DESC')->get();
        return response()->json(['status' => 'success', 'data' => $data], 201);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }

        $notification = FcmNotifications::where('id', $id)->where(function ($query) use ($user) {
            $query->where('who', $user->id)->orWhere('who', 'users');
        })->first();

        if (!$notification) {
            return response()->json(['status' => 'error', 'message' => 'Bildirishnoma topilmadi!'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'message' => 'Bildirishnoma o‘qilgan deb belgilandi.'], 200);
    }


    public function updateFcm(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
        'device_id' => 'required|string',
    ]);

    // 1. Sanctum orqali foydalanuvchini olish (Bearer token orqali)
    $user = Auth::guard('user')->user();

    if (!$user) {
        return response()->json([
            'status' => 'error', 
            'message' => "Sessiya muddati tugagan!"
        ], 401);
    }

    // 2. Aynan shu qurilmani topish va FCM tokenni yangilash
    $updated = DB::table('connected_devices')
        ->where('user_id', $user->id)
        ->where('user_type', 'user') // Agar bu UserAuthController bo'lsa
        ->where('device_id', $request->device_id)
        ->update([
            'fcm_token' => $request->fcm_token,
            'updated_at' => now(),
        ]);

    if ($updated) {
        return response()->json([
            'status' => 'success', 
            'message' => "Bildirishnoma manzili yangilandi"
        ], 200);
    }

    return response()->json([
        'status' => 'error', 
        'message' => "Qurilma topilmadi!"
    ], 404);
}

    /**
     * Shaxsiy ma'lumotlarni yangilash
     */

    public function settings(Request $request)
{
    $user = Auth::guard('user')->user();
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }

    // oddiy maydonlar
    if ($request->filled('name'))      $user->name      = $request->name;
    if ($request->filled('lastname'))  $user->lastname  = $request->lastname;
    if ($request->has('sex'))          $user->sex       = $request->sex;

    if ($request->has('bio')) {
        $user->bio = $request->filled('bio')
            ? mb_substr(strip_tags($request->bio), 0, 120)
            : null;
    }

    // ── Role / Kasb qismi ────────────────────────────────────────────────
    $presetId = $request->input('role_preset_id');   // asosiy parametr

    if ($presetId !== null) {
        if ($presetId == -77) {
            // Custom holat
            $user->role_preset_id = -77;
            $user->role_emoji     = null;  // customda emoji kerak emas deb oldin aytgansiz
            $user->role_title     = $request->filled('role_title')
                ? mb_substr($request->role_title, 0, 100)
                : null;
            $user->role_place     = $request->filled('role_place')
                ? mb_substr($request->role_place, 0, 150)
                : null;
        } 
        else {
            // Oddiy preset tanlangan
            $preset = \App\Models\RolePreset::find($presetId);
            if ($preset) {
                $user->role_preset_id = $preset->id;

                $lang = $user->locale ?? 'uz';
                $field = match ($lang) {
                    'ru' => 'title_ru',
                    'en' => 'title_en',
                    'ja' => 'title_ja',
                    default => 'title_uz',
                };

                $user->role_emoji = $preset->emoji;
                $user->role_title = $preset->$field;

                // joy maydoni faqat needs_place true bo'lsa qabul qilinadi
                if ($preset->needs_place) {
                    $user->role_place = $request->filled('role_place')
                        ? mb_substr($request->role_place, 0, 150)
                        : null;
                } else {
                    $user->role_place = null;
                }
            } else {
                // Noto'g'ri preset_id keldi → o'zgartirmaymiz
            }
        }
    }

    $user->save();

    // javobda ko'rsatish uchun formatlangan role
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
        'role'   => $displayRole,
    ], 200);
}
    
    public function rolePresets(Request $request)
{
    $user = Auth::guard('user')->user();

    $presets = \App\Models\RolePreset::orderBy('sort')
        ->get()
        ->map(function ($r) use ($user) {
            return [
                'id'          => $r->id,
                'emoji'       => $r->emoji,
                'titles'      => [
                    'uz' => $r->title_uz,
                    'ru' => $r->title_ru,
                    'en' => $r->title_en,
                    'ja' => $r->title_ja,
                ],
                'needs_place' => (bool) $r->needs_place,
                'selected'    => $user->role_preset_id == $r->id,
            ];
        });

    return response()->json([
        'status' => 'success',
        'data'   => $presets,
        'current' => [
        'preset_id'   => $user->role_preset_id,
        'title'       => $user->role_title,
        'place'       => $user->role_place,
        'is_custom'   => $user->role_preset_id == -77,
    ]
    ], 200);
}

    /**
     * Mening yetkazib berish manzillarim ro'yxati
     */
    public function my_locations(Request $request)
    {
        $user = Auth::guard('user')->user();
        if ($user) {
            $data = DB::table('locations')->where('user_id', $user->id)->where('isDeleted', false)->get();
            return response()->json(['status' => 'success', 'data' => $data], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
    }


    /**
     * Yangi yetkazib berish manzili qo'shiladi
     */
    public function delete_location(Request $request, Locations $location)
    {
        $user = Auth::guard('user')->user();
        if ($user && $location->id != $user->mainAddressID) {
            $location->isDeleted = true;
            $location->update();
            return response()->json(['status' => 'success', 'message' => "Muvaffaqiyatli bajarildi."], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
    }


    /**
     * Yangi yetkazib berish manzili qo'shiladi
     */
    public function new_location(Request $request)
    {
        $user = Auth::guard('user')->user();
        if ($user) {
            $location = new Locations();
            $location->user_id = $user->id;
            $location->lat = $request->lat;
            $location->lon = $request->lon;
            $location->fullAddress = $request->fullAddress;
            $location->save();
            $user->update(['mainAddressID' => $location->id]);
            return response()->json(['status' => 'success', 'location_id' => $location->id], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
    }

    /**
     * Yetkazib berish manzili tanlandi
     */
    public function select_location(Request $request, string $id)
    {
        $user = Auth::guard('user')->user();
        if ($user) {
            $user->mainAddressID = $id;
            $user->save();
            return response()->json(['status' => 'success', 'message' => "Location set ID:" . $id], 201);
        } else {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 201);
        }
    }


    public function upload_avatar(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $imagePath = $request->file('image')->store('avatar', 'public');
            $user->avatar = $imagePath;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded successfully.',
                'image_url' => $imagePath,
            ], 201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload image.',
            ], 400);
        }
    }

    public function favouriteProducts(Request $request)
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Query with filtering for visible books and sellers
        $favourites = FavouriteProducts::where('user_id', $user->id)
            ->with(['book' => function ($query) {
                $query->where('is_hidden', 0)
                      ->where('count', '>', 0)
                      ->whereHas('seller', function ($sellerQuery) {
                          $sellerQuery->where('is_hidden', 0)->where('status', 'approved');
                      })
                      ->with('seller');
            }])
            ->paginate(20); // Keep pagination

        // Map results, filtering out null books in the response
        $result = $favourites->getCollection()->map(function ($favourite) use ($user) {
            if ($favourite->book) { // Only include non-null books
                return $this->formatBook($favourite->book, $user, true);
            }
            return null;
        })->filter()->values(); 
        $favourites->setCollection(collect($result));

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'pagination' => [
                'current_page' => $favourites->currentPage(),
                'per_page' => $favourites->perPage(),
                'total' => $favourites->total(),
            ],
        ], 201);
    }

    public function addFavourite(Request $request, string $productId)
    {
        $user = Auth::guard('user')->user();

        // Foydalanuvchi topilmasa, 401 xato qaytarish
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $book = Books::find($productId);
        if (!$book) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
            ], 404);
        }

        // Kitob allaqachon sevimli ro'yxatda borligini tekshirish
        $existingFavourite = FavouriteProducts::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingFavourite) {
            $existingFavourite->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Book removed from favourites',
            ], 201);
        }

        // Yangi sevimli kitob qo'shish
        $favourite = FavouriteProducts::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Book added to favourites',
            'data' => $book,
        ], 201);
    }
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

    $user->update([
        'last_seen_at' => now(),
    ]);

    return response()->json(['status' => 'success']);
}
public function updateLocale(Request $request, $locale)
{
    $user = Auth::guard('user')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
        }
    $user && $user->locale != $locale ? $user->update([
        'locale' => $locale,
    ]) : null;

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
        ->where('user_type', 'user') // Faqat "user" turidagilarni olamiz
        ->select('id', 'device_id', 'device_name', 'platform', 'updated_at')
        ->orderBy('updated_at', 'desc')
        ->get();

    // Hozirgi qurilmani belgilab yuborish (ixtiyoriy, front-end uchun qulay)
    // Buning uchun Flutter-dan request-da device_id kelishi kerak
    $currentDeviceId = $request->header('X-Device-Id'); 

    return response()->json([
        'status' => 'success',
        'current_device_id' => $currentDeviceId,
        'data' => $devices
    ], 200);
}

/**
 * Ma'lum bir qurilmani tizimdan chiqarish (Sessiyani yopish)
 */
public function logoutDevice(Request $request, $id)
{
    $user = $request->user();

    // 1. Qurilmani bazadan topamiz
    $device = DB::table('connected_devices')
        ->where('id', $id)
        ->where('user_id', $user->id)
        ->first();

    if (!$device) {
        return response()->json(['status' => 'error', 'message' => 'Qurilma topilmadi'], 404);
    }

    // 2. Sanctum tokenini o'chiramiz 
    // (connected_devices dagi 'token' ustunida hashlangan token saqlangan)
    $user->tokens()->where('token', $device->token)->delete();

    // 3. Qurilmani connected_devices jadvalidan o'chiramiz
    DB::table('connected_devices')->where('id', $id)->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Qurilma muvaffaqiyatli o‘chirildi'
    ], 200);
}
public function logout(Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['status' => 'success']);
}
public function getGlobalCounts(Request $request)
{
    $user = Auth::guard('user')->user();
    $personalUnread = 0;
    $shopUnread = 0;
    $unreadNotifications = 0;
    $cartItems = 0;
    $orderCount = 0;
    $favouriteCount = 0;
    $selectedLocation = false;
    $cards = 0;
    if ($user) {
        $personalUnread = Conversation::where('type', 'personal')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->whereHas('messages', function($query) use ($user) {
                $query->where('is_read', 0)
                      ->where('sender_id', '!=', $user->id);
            })
            ->withCount(['messages as unread_messages' => function($query) use ($user) {
                $query->where('is_read', 0)
                      ->where('sender_id', '!=', $user->id);
            }])
            ->get()
            ->sum('unread_messages');

        // Do'kon xabarlari soni
        $shopUnread = Conversation::where('type', 'shop')
            ->where('user_id', $user->id)
            ->whereHas('messages', function($query) use ($user) {
                $query->where('is_read', 0)
                      ->where('sender_id', '!=', $user->id);
            })
            ->withCount(['messages as unread_messages' => function($query) use ($user) {
                $query->where('is_read', 0)
                      ->where('sender_id', '!=', $user->id);
            }])
            ->get()
            ->sum('unread_messages');

        // Bildirishnomalar
        $unreadNotifications = BookClubNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // Savatdagi mahsulotlar
        $cartItems = MyCart::where('user_id', $user->id)->sum('count_item');

        // Buyurtmalar (Yakunlanmaganlari)
        $orderCount = Sold::where('status', '!=', 'F')
            ->where('user_id', $user->id)
            ->count();

        // Sevimli mahsulotlar
        $favouriteCount = FavouriteProducts::where('user_id', $user->id)->count();
        $selectedLocation = Locations::where('user_id', $user->id)
            ->where('id', $user->mainAddressID)
            ->where('isDeleted', false)
            ->exists();
        $cards = $user->cards()->count();
    }
    return response()->json([
        'status' => 'success',
        'data' => [
            'unread_notifications'      => (int) $unreadNotifications,
            'unread_personal_messages'  => (int) $personalUnread,
            'unread_shop_messages'      => (int) $shopUnread,
            'cart_items'                => (int) $cartItems,
            'pending_orders'            => (int) $orderCount,
            'favourites_count'          => (int) $favouriteCount,
            'selected_location'         => (bool) $selectedLocation,
            'cards'                     => (int) $cards,
            'isVerified'                => $user ? (bool) $user->isVerified : false,
            'isSupport'                 => $user ? (bool) $user->isSupport : false,
            'onPremium'                 => false,
            'onReels'                   => false,
            'ramadan'                   => true,
            'data_required'             => $user ? (bool) $user->firstEdit : false,
            'stopSales'                => false,
        ]
    ]);
}
}
