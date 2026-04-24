<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendSmsController extends Controller
{
    public function __construct(private readonly SmsService $smsService)
    {
    }

    public function sendSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'msg' => 'required|string',
        ]);

        try {
            $payload = $this->smsService->send($request->phone, $request->msg);

            return response()->json([
                'status' => true,
                'data' => $payload,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('SendSmsController xatoligi', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}
