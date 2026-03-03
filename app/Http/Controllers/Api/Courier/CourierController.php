<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Models\DeliveryService;
use App\Models\Sold;
use App\Models\User;
use App\Models\Couriers;
use App\Models\CourierNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Muneebkh2\LaravelFcmNotifications\Facades\LaravelFCM;

class CourierController extends Controller
{
    
    public function getDevices(Request $request)
    {
        $courier = Auth::guard('courier')->user();
    if (!$courier) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    // Hozirgi ishlatilayotgan tokenni hashini olish
    $currentToken = $request->bearerToken();
    $currentTokenHash = hash('sha256', explode('|', $currentToken)[1] ?? $currentToken);

    // JOIN-siz, to'g'ridan-to'g'ri o'zining ustunlari bo'yicha filtrlaymiz
    $devices = DB::table('connected_devices')
        ->where('user_id', $courier->id)
        ->where('user_type', 'courier') // kuryerlar uchun filtr
        ->select('id', 'device_name', 'device_id', 'platform', 'created_at', 'token', 'updated_at')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $devices->map(function ($device) use ($currentTokenHash) {
            return [
                'id' => $device->id,
                'name' => $device->device_name,
                'device_id' => $device->device_id,
                'platform' => $device->platform,
                'created_at' => $device->created_at,
                'last_used_at' => $device->updated_at, 
                'is_current' => $device->token === $currentTokenHash,
            ];
        }),
    ], 200);
}

    public function removeDevice(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $deviceId = $request->input('device_id');
        if (!$deviceId) {
            return response()->json(['success' => false, 'message' => 'Device ID kerak'], 400);
        }
        $device = DB::table('connected_devices')
            ->where('device_id', $deviceId)
            ->whereIn('token', $courier->tokens()->pluck('token'))
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Qurilma topilmadi'], 404);
        }
        DB::transaction(function () use ($device, $courier) {
            $courier->tokens()->where('token', $device->token)->delete();
            DB::table('connected_devices')->where('device_id', $device->device_id)->delete();
        });
        return response()->json([
            'success' => true,
            'message' => 'Qurilma va token muvaffaqiyatli o\'chirildi',
        ], 200);
    }
    public function updateFcm(Request $request)
    {
    $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    if (!$courier) {
        return response()->json(['status' => 'error', 'message' => "Bunday foydalanuvchi mavjud emas!"], 404);
    }
        $courier->fcm_token = $request->token;
        $courier->save();
        return response()->json(['status' => 'success', 'data' => $courier], 201);
    }
    public function notifications(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $notifications = CourierNotification::where('courier_id', $courier->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ], 200);
    }

    public function markAsRead(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        $notificationId = $request->input('notification_id');

        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $notification = CourierNotification::where('courier_id', $courier->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Xabar topilmadi'], 404);
        }

        $notification->isRead = true;
        $notification->save();
        return response()->json(['success' => true], 200);
    }

    public function markAllAsRead(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $notifications = CourierNotification::where('courier_id', $courier->id)
            ->where('isRead', false)
            ->get();
        if ($notifications->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'O\'qilmagan xabarnomalar mavjud emas'
            ], 200);
        }
        foreach ($notifications as $notification) {
            $notification->isRead = true;
            $notification->save();
        }
        return response()->json(['success' => true], 200);
    }

    public function notificationsCount(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $notifications = CourierNotification::where('courier_id', $courier->id)
            ->where('isRead', false)
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ], 200);
    }
    
    public function updatePassword(Request $request)
    {
        $courier = Auth::guard('courier')->user();
        if (!$courier) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if ($courier->password_reset_limit <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik, 1 oyda faqat 3 marotaba parol yangilash mumkin!'
            ], 403);
        }
        $randomPassword = Str::random(12);
        try {
            $courier->update([
                'password' => bcrypt($randomPassword),
                'password_reset_limit' => $courier->password_reset_limit - 1
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Parol muvaffaqiyatli yangilandi',
                'new_password' => $randomPassword
            ], 200);
        } catch (\Exception $e) {
            Log::error('Parolni yangilashda xato', ['courier_id' => $courier->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Parolni yangilashda xatolik yuz berdi'
            ], 500);
        }
    }
}
