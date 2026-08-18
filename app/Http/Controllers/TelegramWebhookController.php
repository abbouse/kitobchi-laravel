<?php

namespace App\Http\Controllers;

use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Nutgram $bot)
    {
        try {
            $bot->run();
        } catch (Throwable $e) {
            Log::error('Telegram webhook run xatosi: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true]);
    }
}