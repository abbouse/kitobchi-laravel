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
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CourierAuthController extends Controller
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly PasswordResetService $passwordResetService
    ) {
    }

    private function findCourierByNormalizedPhone(string $phone): ?Couriers
    {
        return Couriers::query()
            ->where('phone_number', $phone)
            ->orWhereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_number, '+', ''), ' ', ''), '(', ''), ')', ''), '-', '') = ?",
                [$phone]
            )
            ->first();
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

        // Faqat O'zbekiston raqamlarini qabul qilamiz: 998 bilan boshlanib, 12 raqam.
        $rawPhone = preg_replace('/[\s\(\)\-+]/', '', (string) $request->input('phone_number')) ?? '';
        if (!preg_match('/^998\d{9}$/', $rawPhone)) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_phone_not_uz',
                'message'    => __('courier_api.phone_not_uz'),
            ], 422);
        }

        $courier = $this->findCourierByNormalizedPhone($rawPhone);
        $passwordMatches = $courier
            ? Hash::check((string) $request->password, (string) $courier->password)
            : false;

        if (!$courier || !$passwordMatches) {
            Log::warning('Courier auth failed', [
                'phone_number' => $rawPhone,
                'courier_found' => (bool) $courier,
                'courier_id' => $courier?->id,
                'courier_status' => $courier?->status,
                'password_match' => $passwordMatches,
                'device_id' => (string) $request->input('device_id'),
                'platform' => (string) $request->input('platform'),
            ]);
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_invalid_credentials',
                'message'    => __('courier_api.invalid_credentials'),
            ], 401);
        }

        if ($courier->status === 'blocked') {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_account_blocked',
                'message'    => __('courier_api.account_blocked'),
            ], 403);
        }

        if ($courier->status !== 'approved') {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_account_inactive',
                'message'    => __('courier_api.account_inactive'),
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
            'status'  => 'success',
            'message' => __('courier_api.login_success'),
            'token'   => $plainTextToken,
            'courier' => [
                'id'           => $courier->id,
                'first_name'   => $courier->first_name,
                'last_name'    => $courier->last_name,
                'photo'        => $courier->photo,
                'phone_number' => $courier->phone_number,
                'is_online'    => (bool) $courier->is_online,
            ],
        ], 201);
    }
    
    public function contactRequest(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'region'       => 'required|string',
            'name'         => 'required|string',
        ]);

        $rawPhone = preg_replace('/[\s\(\)\-+]/', '', (string) $request->input('phone_number')) ?? '';
        if (!preg_match('/^998\d{9}$/', $rawPhone)) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_phone_not_uz',
                'message'    => __('courier_api.phone_not_uz'),
            ], 422);
        }

        $existingCourier = $this->findCourierByNormalizedPhone($rawPhone);
        if ($existingCourier) {
            if ($existingCourier->status === 'approved') {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'courier_request_already_sent',
                    'message'    => __('courier_api.request_already_sent'),
                ], 403);
            }
            $existingCourier->update([
                'region'     => $request->region,
                'first_name' => $request->name,
            ]);
            return response()->json([
                'status'  => 'success',
                'message' => __('courier_api.request_updated'),
            ], 200);
        }

        Couriers::create([
            'phone_number' => $rawPhone,
            'region'       => $request->region,
            'first_name'   => $request->name,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('courier_api.request_submitted'),
        ], 201);
    }

    public function forgot(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $cleanedPhone = preg_replace('/[\s\(\)\-+]/', '', (string) $request->input('phone_number')) ?? '';
        if (!preg_match('/^998\d{9}$/', $cleanedPhone)) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_phone_not_uz',
                'message'    => __('courier_api.phone_not_uz'),
            ], 422);
        }

        $courier = $this->findCourierByNormalizedPhone($cleanedPhone);

        if (!$courier) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_phone_not_found',
                'message'    => __('courier_api.phone_not_found'),
            ], 404);
        }

        if ($courier->status === 'blocked') {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_account_blocked',
                'message'    => __('courier_api.account_blocked'),
            ], 403);
        }

        if ($courier->status !== 'approved') {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_account_inactive',
                'message'    => __('courier_api.account_inactive'),
            ], 403);
        }

        try {
            $this->passwordResetService->ensureHasAttempts($courier);
        } catch (RuntimeException $e) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_reset_too_many',
                'message'    => __('courier_api.reset_too_many'),
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
                'status'             => 'success',
                'message'            => __('courier_api.reset_sms_sent'),
                'remaining_attempts' => $remaining,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'courier_reset_sms_failed',
                'message'    => __('courier_api.reset_sms_failed'),
            ], 503);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'unauthenticated',
                'message'    => __('courier_api.invalid_credentials'),
            ], 401);
        }

        // Bearer token'ni hash qilib, `connected_devices` jadvalidagi yozuv bilan
        // mos qilish uchun. (`personal_access_tokens.token` va
        // `connected_devices.token` ikkalasi ham hashlangan ko'rinishda saqlanadi.)
        $bearer = $request->bearerToken();
        $hashedToken = (is_string($bearer) && str_contains($bearer, '|'))
            ? hash('sha256', explode('|', $bearer, 2)[1])
            : ($bearer ? hash('sha256', $bearer) : null);

        // Joriy access token'ni o'chiramiz (Sanctum tarafidagi).
        $current = $user->currentAccessToken();
        if ($current) {
            $current->delete();
        }

        // Mos `connected_devices` yozuvini ham o'chirib qo'yamiz.
        if ($hashedToken) {
            ConnectedDevice::where('token', $hashedToken)->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('courier_api.logout_success'),
        ]);
    }
}
