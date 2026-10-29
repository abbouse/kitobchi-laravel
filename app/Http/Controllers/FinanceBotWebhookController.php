<?php

namespace App\Http\Controllers;

use App\Services\FinanceBot\FinanceBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Moliya va Chiqimlar Telegram boti webhook controlleri.
 * X-Telegram-Bot-Api-Secret-Token bilan xavfsiz himoyalangan.
 */
class FinanceBotWebhookController extends Controller
{
    public function __invoke(Request $request, FinanceBotService $bot): JsonResponse
    {
        $secret = (string) config('services.finance_bot.webhook_secret', '');
        if ($secret !== '') {
            $headerSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($secret, $headerSecret)) {
                abort(403, 'Invalid secret token');
            }
        }

        $update = $request->all();

        try {
            if (isset($update['message'])) {
                $bot->handleMessage((array) $update['message']);
            }
        } catch (\Throwable $e) {
            // Telegram bot qayta-qayta yubormasligi uchun 200 qaytarib, xatoni log qilamiz
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}

