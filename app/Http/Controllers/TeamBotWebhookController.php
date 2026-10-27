<?php

namespace App\Http\Controllers;

use App\Services\TeamBot\TeamBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jamoa boti webhooki. Telegram har so'rovda `X-Telegram-Bot-Api-Secret-Token`
 * sarlavhasini yuboradi — TEAM_BOT_WEBHOOK_SECRET bilan mos kelmasa rad etiladi.
 */
class TeamBotWebhookController extends Controller
{
    public function __invoke(Request $request, TeamBotService $bot): JsonResponse
    {
        $secret = (string) config('services.team_bot.webhook_secret', '');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        $update = $request->all();
        try {
            if (isset($update['callback_query'])) {
                $bot->handleCallback((array) $update['callback_query']);
            } elseif (isset($update['message'])) {
                $bot->handleMessage((array) $update['message']);
            }
        } catch (\Throwable $e) {
            // Telegram qayta-qayta yubormasligi uchun baribir 200 qaytaramiz
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}
