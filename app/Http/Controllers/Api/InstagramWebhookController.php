<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstagramBotService;
use Illuminate\Http\Request;

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
}
