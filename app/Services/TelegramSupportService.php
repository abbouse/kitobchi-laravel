<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;

class TelegramSupportService
{
    public function sendText(int|string $chatId, string $message): array
    {
        $token = (string) config('nutgram.token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token sozlanmagan.');
        }

        $chunks = $this->splitMessage($message);
        $lastPayload = [];

        foreach ($chunks as $chunk) {
            try {
                $lastPayload = $this->sendTextViaHttp($token, $chatId, $chunk);
            } catch (\Throwable $httpException) {
                Log::warning('Telegram support reply HTTP yuborish yiqildi, Nutgram fallback ishlatiladi', [
                    'chat_id' => $chatId,
                    'error' => $httpException->getMessage(),
                ]);

                $lastPayload = $this->sendTextViaNutgram($token, $chatId, $chunk);
            }
        }

        return $lastPayload;
    }

    /**
     * @return list<string>
     */
    private function splitMessage(string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException('Yuboriladigan matn bo‘sh.');
        }

        if (mb_strlen($message) <= 4096) {
            return [$message];
        }

        $chunks = [];
        $offset = 0;
        $length = mb_strlen($message);

        while ($offset < $length) {
            $chunks[] = mb_substr($message, $offset, 4096);
            $offset += 4096;
        }

        return $chunks;
    }

    private function sendTextViaHttp(string $token, int|string $chatId, string $message): array
    {
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
            throw new RuntimeException(
                is_array($payload)
                    ? (string) ($payload['description'] ?? 'Telegram API xatosi.')
                    : 'Telegram API noto‘g‘ri javob qaytardi.'
            );
        }

        return $payload;
    }

    private function sendTextViaNutgram(string $token, int|string $chatId, string $message): array
    {
        $bot = new Nutgram($token);
        $telegramMessage = $bot->sendMessage($message, chat_id: (int) $chatId);

        return [
            'ok' => true,
            'result' => [
                'message_id' => $telegramMessage?->message_id,
            ],
        ];
    }
}
