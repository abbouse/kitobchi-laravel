<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Models\Couriers;
use App\Models\ConnectedDevice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CourierAuthController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    public function auth(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
            'device_id' => 'required|string',
            'device_name' => 'required|string',
            'platform' => 'required|string',
            'fcm_token'    => 'nullable|string',
        ]);
        $courier = Couriers::where('phone_number', $request->phone_number)->first();

        if (!$courier || !Hash::check($request->password, $courier->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telefon raqam yoki parol noto‘g‘ri!'
            ], 401);
        }

        if ($courier->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Sizning hisobingiz faol emas. Iltimos, administrator bilan bog\'laning.'
            ], 403);
        }

        $tokenResult = $courier->createToken('courier-token');
        $plainTextToken = $tokenResult->plainTextToken;
        $hashedToken = hash('sha256', explode('|', $plainTextToken)[1]);

        DB::table('connected_devices')->updateOrInsert(
            ['device_id' => $request->device_id],
            [
                'user_id' => $courier->id,
                'user_type' => 'courier',
                'token' => $hashedToken,
                'fcm_token' => $request->fcm_token,
                'device_name' => $request->device_name,
                'platform' => $request->platform,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $activeTokensInDevices = DB::table('connected_devices')
            ->where('user_id', $courier->id)
            ->where('user_type', 'courier')
            ->pluck('token')
            ->toArray();

        $courier->tokens()->whereNotIn('token', $activeTokensInDevices)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tizimga muvaffaqiyatli kirdingiz!',
            'token' => $plainTextToken,
            'courier' => [
                'id' => $courier->id,
                'first_name' => $courier->first_name,
                'last_name' => $courier->last_name,
                'photo' => $courier->photo,
                'phone_number' => $courier->phone_number,
                ],
        ], 201);
    }
    
    public function contactRequest(Request $request)
{
    $request->validate([
        'phone_number' => 'required|string',
        'region' => 'required|string',
        'name' => 'required|string',
    ]);
    $existingCourier = Couriers::where('phone_number', $request->phone_number)->first();
    if ($existingCourier) {
        if ($existingCourier->status === 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => "Siz allaqachon ro‘yxatdan o‘tgansiz yoki faol kuryersiz",
            ], 403);
        }
        $existingCourier->update([
            'region' => $request->region,
            'first_name' => $request->name,
        ]);
        return response()->json([
            'status' => 'success',
            'message' => "So‘rov ma’lumotlari yangilandi",
        ], 200);
    }
    Couriers::create([
        'phone_number' => $request->phone_number,
        'region' => $request->region,
        'first_name' => $request->name,
    ]);
    return response()->json([
        'status' => 'success',
        'message' => "So‘rov muvaffaqiyatli yuborildi",
    ], 201);
}

    public function forgot(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $cleanedPhone = preg_replace('/[\s\(\)-]/', '', $request->phone_number);
        $courier = Couriers::where('phone_number', $cleanedPhone)->first();

        if (!$courier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Telefon raqami topilmadi.',
            ], 404);
        }

        if ($courier->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Bunday kuryer topilmadi yoki hisob faol emas.',
            ], 403);
        }

        try {
            $this->passwordResetService->ensureHasAttempts($courier);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 429);
        }

        try {
            $newPassword = $this->passwordResetService->generatePassword();
            $this->smsService->send(
                $courier->phone_number,
                "Kitobchi Express: sizning yangi parolingiz — {$newPassword}"
            );
            $remaining = $this->passwordResetService->applyNewPassword($courier, $newPassword);

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

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        ConnectedDevice::where('token', $request->bearerToken())->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Siz tizimdan chiqdingiz.'
        ]);
    }
}
