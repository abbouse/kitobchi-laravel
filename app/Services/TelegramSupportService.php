<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;

class TelegramSupportService
{
    public function sendText(int|string $chatId, string $message, ?string $parseMode = 'HTML', ?int $replyToMessageId = null): array
    {
        $token = (string) config('nutgram.token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token sozlanmagan.');
        }

        $chunks = $this->splitMessage($message);
        $lastPayload = [];

        foreach ($chunks as $i => $chunk) {
            $isLast = ($i === count($chunks) - 1);
            $replyTo = $isLast ? $replyToMessageId : null;

            try {
                $lastPayload = $this->sendTextViaHttp($token, $chatId, $chunk, $parseMode, $replyTo);
            } catch (\Throwable $httpException) {
                Log::warning('Telegram support reply HTTP yuborish yiqildi, Nutgram fallback ishlatiladi', [
                    'chat_id' => $chatId,
                    'error'   => $httpException->getMessage(),
                ]);

                $lastPayload = $this->sendTextViaNutgram($token, $chatId, $chunk, $parseMode, $replyTo);
            }
        }

        return $lastPayload;
    }

    public function sendChatAction(int|string $chatId, string $action = 'typing'): void
    {
        $token = (string) config('nutgram.token');
        if ($token === '') return;

        try {
            Http::asForm()
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendChatAction", [
                    'chat_id' => (string) $chatId,
                    'action'  => $action,
                ]);
        } catch (\Throwable) {}
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

    private function sendTextViaHttp(string $token, int|string $chatId, string $message, ?string $parseMode = 'HTML', ?int $replyTo = null): array
    {
        $params = [
            'chat_id' => (string) $chatId,
            'text'    => $message,
        ];

        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        if ($replyTo) {
            $params['reply_to_message_id'] = $replyTo;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", $params);

        $payload = $response->json();

        // If HTML parsing failed, try sending plain text
        if (!$response->successful() && $parseMode !== null && str_contains($response->body(), "can't parse entities")) {
            unset($params['parse_mode']);
            $retryResponse = Http::asForm()
                ->timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $params);
            
            $payload = $retryResponse->json();
            if ($retryResponse->successful() && ($payload['ok'] ?? false)) {
                return $payload;
            }
        }

        if (!$response->successful()) {
            Log::error('Telegram support reply HTTP xatosi', [
                'chat_id' => $chatId,
                'status'  => $response->status(),
                'body'    => $response->body(),
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

    private function sendTextViaNutgram(string $token, int|string $chatId, string $message, ?string $parseMode = 'HTML', ?int $replyTo = null): array
    {
        /** @var Nutgram $bot */
        $bot = app(Nutgram::class);
        $telegramMessage = $bot->sendMessage(
            text: $message,
            chat_id: (int) $chatId,
            parse_mode: $parseMode,
            reply_to_message_id: $replyTo
        );

        return [
            'ok' => true,
            'result' => [
                'message_id' => $telegramMessage?->message_id,
            ],
        ];
    }
}
