<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramSupportService
{
    public function sendText(int|string $chatId, string $message): array
    {
        $token = (string) config('nutgram.token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token sozlanmagan.');
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => (string) $chatId,
                'text' => $message,
            ]);

        $payload = $response->json();

        if (!$response->successful()) {
            Log::error('Telegram support reply HTTP xatosi', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                is_array($payload) && !empty($payload['description'])
                    ? (string) $payload['description']
                    : 'Telegram API bilan bog‘lanib bo‘lmadi.'
            );
        }

        if (!is_array($payload) || !($payload['ok'] ?? false)) {
            $description = is_array($payload)
                ? (string) ($payload['description'] ?? 'Telegram API xatosi.')
                : 'Telegram API noto‘g‘ri javob qaytardi.';

            Log::warning('Telegram support reply API xatosi', [
                'chat_id' => $chatId,
                'payload' => $payload,
            ]);

            throw new RuntimeException($description);
        }

        return $payload;
    }
}
