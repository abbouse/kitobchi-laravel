<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $randomString = Str::random(10);
    $phone_number = str_replace([' ', '-'], '', $request->phone_number);

    // --- 1-BOSQICh: SMS yuborish ---
    if (strlen($phone_number) == 12 && is_null($request->verifyCode)) {
        $verifyCode = ($phone_number == '998331234567') ? '222222' : rand(100001, 999999);

        if ($phone_number != '998331234567') {
            Http::post(route('api.sendSms'), [
                'phone' => $phone_number,
                'msg' => "<#> Kitobchi ilovasida tasdiqlash uchun kod: $verifyCode. $randomString",
            ]);
        }

        $user = User::where('phone_number', $phone_number)->where('isDeleted', 'no')->first();

        if (!$user) {
            User::create([
                'phone_number' => $phone_number,
                'verifyCode' => $verifyCode,
                'password' => bcrypt('kitobchi122'),
            ]);
        } else {
            $user->update(['verifyCode' => $verifyCode]);
        }

        return response()->json(['status' => 'success', 'message' => 'Kod yuborildi'], 201);
    }

    // --- 2-BOSQICh: Kodni tasdiqlash va Device saqlash ---
    $user = User::where('phone_number', $phone_number)->where('isDeleted', 'no')->first();

    if ($user && $user->verifyCode == $request->verifyCode) {
        
        // Token yaratish
        $tokenResult = $user->createToken('user_token');
        $plainTextToken = $tokenResult->plainTextToken;
        $hashedToken = hash('sha256', explode('|', $plainTextToken)[1]);

        // ✅ Qurilmani saqlash mantiqi
        if ($request->has('device_id')) {
            DB::table('connected_devices')->updateOrInsert(
                ['device_id' => $request->device_id],
                [
                    'user_id'     => $user->id,
                    'user_type'   => 'user', // User turi
                    'token'       => $hashedToken,
                    'fcm_token'   => $request->fcm_token, // Flutterdan keladi
                    'device_name' => $request->device_name ?? 'Unknown Device',
                    'platform'    => $request->platform ?? 'Unknown',
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        // Eskirgan tokenlarni tozalash
        $activeTokens = DB::table('connected_devices')
            ->where('user_id', $user->id)
            ->where('user_type', 'user')
            ->pluck('token')->toArray();
        $user->tokens()->whereNotIn('token', $activeTokens)->delete();

        $user->update(['verifyCode' => null, 'verified' => 1]);
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number,
                    'name' => $user->name,
                    'lastname' => $user->lastname,
                    'sex' => $user->sex,
                    'token' => $plainTextToken,
                    'cartItemCount' => $user->cart()->sum('count_item'),
                    'photo' => $user->avatar,
                    'real_balance' => $user->real_balance ?? 0,
                    'cashback' => $user->cashback ?? 0,
                    'mainAddressID' => $user->mainAddressID ?? 0,
                    'user_main_location' => $user->location->fullAddress ?? "",
                ],
            ], 201);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Tekshiruv kodi xato kiritildi',
        ], 400);
    }

    /**
     * Tokenni tekshirish.
     */
    public function checkToken(?string $token)
    {
        $user = Auth::guard('user')->user();

        if ($user) {
            return response()->json([
                'status' => 'success',
                'mode' => 'user',
                'message' => 'Token aktual',
            ], 201);
        }
        if ($token == "null" && $user == null) {
            return response()->json([
                'status' => 'success',
                'mode' => 'guest',
                'message' => 'Guest mode',
            ], 201);
        }else{
            return response()->json([
                'status' => 'error',
                'mode' => 'unauthorized',
                'message' => 'Token aktual emas!',
            ], 401);
        }
    }

}
