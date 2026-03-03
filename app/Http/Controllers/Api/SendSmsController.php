<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsController extends Controller
{
    public function sendSms(Request $request)
{
    Log::info('Request:', $request->all());

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->getToken(),
        'Accept' => 'application/json',
    ])->post('https://notify.eskiz.uz/api/message/sms/send', [
        'mobile_phone' => $request->phone,
        'message' => $request->msg,
        'from' => '4546',
    ]);

    Log::info('Eskiz API Response:', $response->json());

    if ($response->successful()) {
        return Response::api($response->json());
    } else {
        Log::error('Eskiz API Error:', $response->json());
        return Response::json([
            'status' => false,
            'message' => 'SMS yuborilmadi. Iltimos, keyinroq qayta urinib ko\'ring.'
        ], 500);
    }
}

public function getToken()
{
    $token = Cache::get('eskiz_api_token');
    if (!$token) {
        $response = Http::post('https://notify.eskiz.uz/api/auth/login', [
            'email' => 'toordaliev@gmail.com',
            'password' => 'aF6WH2CcaKes30zgLPCZ1MM7CPfPlgCX07HoM8rE',
        ]);

        if ($response->successful()) {
            $token = $response['data']['token'];
            Cache::put('eskiz_api_token', $token, now()->addDays(30));
        } else {
            Log::error('Eskiz Token Error:', $response->json());
            abort(500, 'Eskiz API bilan bog\'lanishning imkoni bo\'lmadi.');
        }
    }

    return $token;
}

}