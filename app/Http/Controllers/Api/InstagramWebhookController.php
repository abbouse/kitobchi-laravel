<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstagramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InstagramWebhookController extends Controller
{
    protected InstagramBotService $botService;

    public function __construct(InstagramBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Meta Webhook Verification Handshake (GET /api/instagram/webhook)
     */
    public function verify(Request $request)
    {
        return $this->botService->verifyWebhook($request);
    }

    /**
     * Handle incoming Meta Instagram Events (POST /api/instagram/webhook)
     */
    public function handle(Request $request)
    {
        return $this->botService->handlePayload($request->all());
    }

    /**
     * Diagnostic Test Endpoint (GET /api/instagram/test)
     */
    public function test(Request $request)
    {
        $token = config('services.instagram.page_access_token', env('INSTAGRAM_PAGE_ACCESS_TOKEN'));

        if (!$token) {
            return response()->json([
                'error' => 'INSTAGRAM_PAGE_ACCESS_TOKEN is missing or empty in .env!',
                'token_configured' => false
            ], 400);
        }

        $response = Http::get("https://graph.facebook.com/v19.0/me?access_token={$token}");

        return response()->json([
            'token_configured' => true,
            'token_prefix' => substr($token, 0, 10) . '...',
            'token_length' => strlen($token),
            'meta_status' => $response->status(),
            'meta_response' => $response->json(),
        ]);
    }
}
