<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Nutgram;

class WebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Nutgram $bot): void
    {
        // Telegram'ning maxfiy tokeni sozlangan bo'lsa — soxta "update"lar rad
        // etiladi (setWebhook secret_token bilan o'rnatilganda Telegram uni
        // X-Telegram-Bot-Api-Secret-Token sarlavhasida yuboradi)
        $secret = (string) config('services.telegram.webhook_secret', '');
        if ($secret !== '' && ! hash_equals($secret, (string) request()->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        try {
            $bot->run();
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
        }
    }
}