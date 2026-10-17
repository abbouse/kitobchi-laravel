<?php

namespace App\Http\Controllers;

use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Nutgram $bot)
    {
        // TELEGRAM_WEBHOOK_SECRET o'rnatilgan bo'lsa (setWebhook ... secret_token)
        // — Telegram'dan kelmagan soxta so'rovlar rad etiladi
        $secret = (string) config('services.telegram.webhook_secret', '');
        if ($secret !== '' && ! hash_equals($secret, (string) request()->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        try {
            $bot->run();
        } catch (Throwable $e) {
            Log::error('Telegram webhook run xatosi: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => false], 200);
        }

        return response()->json(['ok' => true]);
    }
}