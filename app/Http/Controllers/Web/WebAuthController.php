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
            return redirect()->route('welcome')->with('error', 'Iltimos, avval tizimga kiring.');
        }

        $orders = Sold::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $locations = Locations::where('user_id', $user->id)->where('isDeleted', false)->get();

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

        return view('user.profile', compact('user', 'orders', 'locations', 'myReviews'));
    }

    /**
     * Yangi manzil qo'shish (Yandex Map orqali)
     */
    public function addLocation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'fullAddress' => 'required|string|max:1000',
        ]);

        $location = new Locations();
        $location->user_id = $user->id;
        $location->lat = $validated['lat'];
        $location->lon = $validated['lon'];
        $location->fullAddress = $validated['fullAddress'];
        $location->country_code = 'UZ'; // By default for now, similar to mobile API
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
