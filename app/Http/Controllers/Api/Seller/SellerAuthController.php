<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Services\PasswordResetService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SellerAuthController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    public function register(Request $request)
    {
        $phone_number = $request->input('phone_number');
        $shop_name = $request->input('shop_name');
        $region = $request->input('region');
        $activity_types = $request->input('activity_types');
        $cleaned_phone = preg_replace('/[\s\(\)-]/', '', $phone_number);
        $sellerCheck = Seller::where('phone_number', $cleaned_phone)->first();
        
        $error = '';
        if (empty($phone_number)) {
            $error = 'Telefon raqami bo‘sh bo‘lmasligi kerak';
        }
        if (Seller::where('phone_number', $cleaned_phone)->exists()) {
            $error = 'Bu telefon raqami allaqachon ro‘yxatdan o‘tgan';
        }
        if (empty($shop_name)) {
            $error = 'Do‘kon nomi bo‘sh bo‘lmasligi kerak';
        } elseif (strlen($shop_name) > 55) {
            $error = 'Do‘kon nomi 55 belgidan oshmasligi kerak';
        }
        if (empty($region)) {
            $error = 'Viloyat bo‘sh bo‘lmasligi kerak';
        }
        if (empty($activity_types)) {
            $error = 'Faoliyat turlari bo‘sh bo‘lmasligi kerak';
        } elseif (!is_array($activity_types)) {
            $error = 'Faoliyat turlarida xatolik';
        } else {
            $allowed_types = ['Kitob', 'Kanstovar'];
            foreach ($activity_types as $type) {
                if (!in_array($type, $allowed_types)) {
                    $error = "Noto‘g‘ri faoliyat turi: $type. Ruxsat etilgan: Kitob, Kanstovar";
                }
            }
        }
        if ($error) {
            return response()->json([
                'message' => $error
            ], 422);
        }

        // ✅ OWNER yaratiladi (parent_id = NULL)
        $seller = Seller::create([
            'phone_number' => $cleaned_phone,
            'shop_name' => $shop_name,
            'region' => $region,
            'activity_types' => json_encode($activity_types),
            'status' => 'pending',
            'role' => 1, // ✅ OWNER = Admin role
            'parent_id' => 0, // ✅ OWNER
            'password' => (string) Str::random(10),
        ]);

        return response()->json([
            'status' => 'success',
            'seller' => "Biz siz bilan aloqaga chiqamiz",
        ], 201);
    }

    /**
     * ✅ OWNER DO'KON MA'LUMOTLARINI QAYTARADI
     */
    private function getStoreSellerData($seller)
    {
        if (!$seller->parent_id) {
            return $seller;
        }
        return Seller::where('id', $seller->parent_id)->first();
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'password' => 'required',
            'device_id' => 'required',
            'device_name' => 'required',
            'platform' => 'required',
            'fcm_token' => 'nullable',
        ]);

        $cleaned_phone = preg_replace('/[\s\(\)-]/', '', $request->phone_number);
        $seller = Seller::where('phone_number', $cleaned_phone)->first();

        if ($seller && $seller->status === 'blocked') {
            return response()->json(['message' => 'Profilingiz admin tomonidan bloklangan. Qo‘llab-quvvatlash bilan bog‘laning.'], 200);
        }

        if (!$seller || $seller->status != 'approved' || !Hash::check($request->password, $seller->password)) {
            return response()->json(['message' => 'Login yoki parol xato yoki profilingiz faol emas'], 200);
        }

        // Token yaratish
        $tokenResult = $seller->createToken('seller-token');
        $plainTextToken = $tokenResult->plainTextToken;
        
        // Sanctum tokenni bazada shunday saqlaydi (solishtirish uchun kerak)
        $hashedToken = hash('sha256', explode('|', $plainTextToken)[1]);

        // Qurilmani saqlash
        DB::table('connected_devices')->updateOrInsert(
            ['device_id' => $request->device_id],
            [
                'user_id'     => $seller->id,
                'user_type'   => 'seller',
                'token'       => $hashedToken,
                'fcm_token'   => $request->fcm_token, // Endi xato bermaydi
                'device_name' => $request->device_name ?? 'Unknown Device',
                'platform'    => $request->platform ?? 'Unknown Platform',
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        // Eskirgan tokenni tozalash (Faqat shu qurilmadan tashqaridagilar)
        $activeTokens = DB::table('connected_devices')
            ->where('user_id', $seller->id)
            ->where('user_type', 'seller')
            ->pluck('token')
            ->toArray();

        $seller->tokens()->whereNotIn('token', $activeTokens)->delete();

        $storeSeller = $this->getStoreSellerData($seller);

        return response()->json([
            'status' => 'success',
            'seller' => [
                'id' => $seller->id,
                'phone_number' => $seller->phone_number,
                'shop_name' => $storeSeller->shop_name,
                'region' => $storeSeller->region,
                'activity_types' => $storeSeller->activity_types,
                'photo' => $storeSeller->photo,
                'status' => $storeSeller->status,
                'firstname' => $seller->firstname,
                'lastname' => $seller->lastname,
                'role' => $seller->role,
                'parent_id' => $seller->parent_id,
                'balance' => $storeSeller->balance,
                'created_at' => $storeSeller->created_at,
            ],
            'token' => $plainTextToken,
        ]);
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $cleaned_phone = preg_replace('/[\s\(\)-]/', '', $request->phone_number);
        $seller = Seller::where('phone_number', $cleaned_phone)->first();
        
        if (!$seller) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telefon raqami topilmadi.',
            ], 404);
        }

        if ($seller->status === 'blocked') {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil admin tomonidan bloklangan. Parolni tiklash mumkin emas.',
            ], 403);
        }
        
        if ($seller->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Bunday foydalanuvchi topilmadi',
            ], 403);
        }

        try {
            $this->passwordResetService->ensureHasAttempts($seller);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 429);
        }

        try {
            $newPassword = $this->passwordResetService->generatePassword();
            $this->smsService->send(
                $seller->phone_number,
                "Kitobchi Business: sizning yangi parolingiz — {$newPassword}"
            );
            $remaining = $this->passwordResetService->applyNewPassword($seller, $newPassword);

            return response()->json([
                'status' => 'success',
                'message' => 'Yangi parol telefon raqamiga SMS tarzida yuborildi.',
                'remaining_attempts' => $remaining,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parolni tiklashda xatolik yuz berdi. Iltimos, keyinroq qayta urinib ko‘ring.',
            ], 503);
        }
    }
}
