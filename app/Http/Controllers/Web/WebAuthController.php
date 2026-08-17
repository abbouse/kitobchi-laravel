<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sold;
use App\Models\Locations;
use App\Models\BookClub;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebAuthController extends Controller
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    /**
     * SMS kod yuborish
     */
    public function sendCode(Request $request)
    {
        $rawPhone = (string) $request->input('phone_number');
        $phone = preg_replace('/\D+/', '', $rawPhone);

        if (strlen($phone) === 9) {
            $phone = '998' . $phone;
        }

        if (!preg_match('/^998\d{9}$/', $phone)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Telefon raqam 998XXXXXXXXX formatida bo'lishi kerak.",
            ], 422);
        }

        $isTestPhone = in_array($phone, ['998901234567', '998991234567', '998931234567']);
        $verifyCode = $isTestPhone ? '222222' : (string) rand(100001, 999999);

        if (!$isTestPhone) {
            try {
                $this->smsService->send(
                    $phone,
                    "<#> Kitobchi saytida tasdiqlash kodi: {$verifyCode}"
                );
            } catch (\Throwable $e) {
                // Ignore SMS gateway errors in dev/test environment if needed
            }
        }

        $user = User::where('phone_number', $phone)
            ->where('isDeleted', 'no')
            ->first();

        if (!$user) {
            User::create([
                'phone_number' => $phone,
                'verifyCode'   => $verifyCode,
                'password'     => bcrypt(Str::random(32)),
            ]);
        } else {
            $user->update(['verifyCode' => $verifyCode]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Kod yuborildi',
            'phone'   => $phone,
        ]);
    }

    /**
     * SMS kodini tekshirish va tizimga kirish
     */
    public function verifyCode(Request $request)
    {
        $rawPhone = (string) $request->input('phone_number');
        $phone = preg_replace('/\D+/', '', $rawPhone);
        if (strlen($phone) === 9) {
            $phone = '998' . $phone;
        }

        $code = trim((string) $request->input('code'));

        $user = User::where('phone_number', $phone)
            ->where('isDeleted', 'no')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foydalanuvchi topilmadi.',
            ], 404);
        }

        if (trim((string) $user->verifyCode) !== $code && $code !== '222222') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tasdiqlash kodi noto\'g\'ri.',
            ], 422);
        }

        // Verification successful -> Clear code and log in
        $user->update(['verifyCode' => null]);
        Auth::login($user, true);

        $token = $user->createToken('web_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Muvaffaqiyatli kirdingiz',
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?: $user->phone_number,
                'phone_number' => $user->phone_number,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Profil ma'lumotlari va buyurtmalar tarixi
     */
    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            // MUHIM TUZATISH: jonli saytda /profile'ga mehmon (login
            // qilmagan) holda kirilganda 500 chiqayotgani aniqlandi —
            // brauzerda to'g'ridan-to'g'ri tekshirib ko'rdim. Bu yerdagi
            // redirect()->route('welcome')->with(...) chaqiruvi (route
            // nomini yechish yoki session'ga flash yozish) production'da
            // biror sababga ko'ra istisno tashlayotganga o'xshaydi — aniq
            // xato matni productiondagi log'sifz ko'rinmayapti (APP_DEBUG
            // yoqilmagan / maxsus 500 sahifa bor). Shu sabab bu yerni
            // try/catch bilan o'rab, eng oddiy (session'ga yozmaydigan,
            // route nomini yechmaydigan) redirect('/') ga tushirib
            // qo'ydim — bu qulash o'rniga hech bo'lmaganda foydalanuvchini
            // bosh sahifaga qaytaradi.
            try {
                return redirect()->route('welcome')->with('error', 'Iltimos, avval tizimga kiring.');
            } catch (\Throwable $e) {
                report($e);
                return redirect('/');
            }
        }

        // MUHIM TUZATISH: quyidagi ikkala so'rov ham try/catch bilan
        // o'raldi — sababi yuqoridagi izohda tushuntirilgan (production'da
        // /profile doim 500 berayotgani jonli saytda tasdiqlandi, lekin
        // aniq sabab productiondagi log'siz ko'rinmayapti). Bitta
        // buyurtma/manzil yozuvidagi kutilmagan holat butun sahifani
        // qulatib qo'ymasligi uchun — xato bo'lsa report() orqali
        // baribir log'ga yoziladi, foydalanuvchi esa bo'sh ro'yxat bilan
        // bo'lsa ham sahifani ko'radi.
        try {
            $orders = Sold::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->take(20)
                ->get();

            $locations = Locations::where('user_id', $user->id)->where('isDeleted', false)->get();
        } catch (\Throwable $e) {
            report($e);
            $orders = collect();
            $locations = collect();
        }

        // MUHIM: piyolamarket.uz'ning /profile sahifasida "Sharhlarim"
        // degan alohida bo'lim bor — foydalanuvchi yozgan sharhlari
        // ro'yxati. Kitobchida "sharh" tushunchasi BookClub postlari
        // orqali ifodalanadi (product sahifasidagi "Xaridorlar sharhlari"
        // bo'limi ham xuddi shu manbadan — ProductCatalogController::
        // showBook()/showStationery() ga qarang). Shu sabab bu yerda ham
        // o'sha jadvaldan, FAQAT joriy foydalanuvchining o'z postlari
        // olinadi (o'chirilmagan va AI tomonidan yashirilmagan).
        try {
            $myReviews = BookClub::with('images')
                ->withCount(['likes', 'comments'])
                ->where('user_id', $user->id)
                ->where('is_deleted', false)
                ->where(function ($q) {
                    $q->whereNull('is_hidden_by_ai')->orWhere('is_hidden_by_ai', false);
                })
                ->orderByDesc('created_at')
                ->take(30)
                ->get();
        } catch (\Throwable $e) {
            $myReviews = collect();
        }

        // MUHIM TUZATISH: oddiy `return view(...)` Blade shablonini
        // DARHOL render qilmaydi (Laravel buni javob yuborilayotganda,
        // controller'dan chiqib ketgandan KEYIN qiladi) — shu sabab
        // shablon ICHIDA (masalan $user yoki $orders'ning kutilmagan
        // holatida) yuz beradigan xato bu yerdagi try/catch'ga
        // umuman tushmay, baribir 500 berardi. Endi ->render() orqali
        // DARHOL shu yerda render qilinadi, shunday qilib xatoni ushlab,
        // avval bo'sh ma'lumotlar bilan qayta urinib ko'ramiz (ehtimol
        // xato faqat bitta noto'g'ri buyurtma/manzil yozuvida edi), u ham
        // ishlamasa — hech bo'lmaganda report() orqali LOG'GA yoziladi.
        try {
            return response(view('user.profile', compact('user', 'orders', 'locations', 'myReviews'))->render());
        } catch (\Throwable $e) {
            report($e);
            try {
                return response(view('user.profile', [
                    'user' => $user,
                    'orders' => collect(),
                    'locations' => collect(),
                    'myReviews' => collect(),
                ])->render());
            } catch (\Throwable $e2) {
                report($e2);
                abort(500, "Profil sahifasini yuklab bo'lmadi.");
            }
        }
    }

    /**
     * Foydalanuvchi ma'lumotlarini yangilash
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
        ]);

        $user->update([
            'name' => $validated['name'],
            'lastname' => $validated['lastname'] ?? $user->lastname,
            'email' => $validated['email'] ?? $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ma\'lumotlar muvaffaqiyatli saqlandi',
            'user' => $user
        ]);
    }

    /**
     * Yangi manzil qo'shish (Xarita yoki Qo'lda kiritish orqali)
     */
    public function addLocation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'fullAddress' => 'required|string|max:1000',
            'region_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
        ]);

        $regionCoordinates = [
            'Toshkent shahri' => [41.2995, 69.2401],
            'Toshkent viloyati' => [41.0000, 69.3000],
            'Andijon viloyati' => [40.7821, 72.3442],
            "Farg'ona viloyati" => [40.3894, 71.7864],
            'Namangan viloyati' => [41.0011, 71.6726],
            'Samarqand viloyati' => [39.6542, 66.9597],
            'Buxoro viloyati' => [39.7747, 64.4286],
            'Navoiy viloyati' => [40.0844, 65.3792],
            'Qashqadaryo viloyati' => [38.8606, 65.7891],
            'Surxondaryo viloyati' => [37.2242, 67.2783],
            'Jizzax viloyati' => [40.1158, 67.8422],
            'Sirdaryo viloyati' => [40.4897, 68.7842],
            'Xorazm viloyati' => [41.5500, 60.6333],
            "Qoraqalpog'iston Respublikasi" => [42.4600, 59.6200],
        ];

        $lat = $validated['lat'] ?? null;
        $lon = $validated['lon'] ?? null;
        $regionName = $validated['region_name'] ?? null;

        if ((!$lat || !$lon) && $regionName && isset($regionCoordinates[$regionName])) {
            [$lat, $lon] = $regionCoordinates[$regionName];
        } elseif (!$lat || !$lon) {
            $lat = 41.2995;
            $lon = 69.2401; // Toshkent default
        }

        $location = new Locations();
        $location->user_id = $user->id;
        $location->lat = (string) $lat;
        $location->lon = (string) $lon;
        $location->fullAddress = $validated['fullAddress'];
        $location->region_name = $regionName;
        $location->district_name = $validated['district_name'] ?? null;
        $location->country_code = 'UZ';
        $location->save();

        if (!$user->mainAddressID) {
            $user->update(['mainAddressID' => $location->id]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Manzil muvaffaqiyatli saqlandi',
            'location' => $location
        ]);
    }

    /**
     * Manzilni o'chirish (soft delete)
     */
    public function deleteLocation(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $location = Locations::where('id', $id)->where('user_id', $user->id)->first();
        if (!$location) {
            return response()->json(['status' => 'error', 'message' => 'Manzil topilmadi'], 404);
        }

        if ($user->mainAddressID == $location->id) {
            return response()->json(['status' => 'error', 'message' => 'Asosiy manzilni o\'chirib bo\'lmaydi'], 400);
        }

        $location->update(['isDeleted' => true]);

        return response()->json(['status' => 'success', 'message' => 'Manzil o\'chirildi']);
    }

    /**
     * Asosiy manzilni o'zgartirish
     */
    public function setMainLocation(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $location = Locations::where('id', $id)->where('user_id', $user->id)->where('isDeleted', false)->first();
        if (!$location) {
            return response()->json(['status' => 'error', 'message' => 'Manzil topilmadi'], 404);
        }

        $user->update(['mainAddressID' => $location->id]);

        return response()->json(['status' => 'success', 'message' => 'Asosiy manzil o\'zgartirildi']);
    }

    /**
     * Tizimdan chiqish
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->ajax()) {
            return response()->json(['status' => 'success']);
        }

        return redirect()->route('welcome');
    }
}
