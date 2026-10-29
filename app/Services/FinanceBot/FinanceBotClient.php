<?php

namespace App\Services\FinanceBot;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Moliya va chiqimlar boti uchun Telegram Bot API mijozi.
 *
 * Jamoa va mijozlar botlaridan mustaqil ishlaydi (FINANCE_BOT_TOKEN).
 */
class FinanceBotClient
{
    public function enabled(): bool
    {
        return (bool) config('services.finance_bot.enabled') && $this->token() !== '';
    }

    public function token(): string
    {
        return (string) config('services.finance_bot.token', '');
    }

    public function defaultChatId(): ?string
    {
        $id = (string) config('services.finance_bot.chat_id', '');

        return $id !== '' ? $id : Cache::get('finance_bot:chat_id');
    }

    public function defaultThreadId(): ?int
    {
        $threadId = config('services.finance_bot.thread_id');
        if ($threadId !== null && $threadId !== '') {
            return (int) $threadId;
        }

        $cached = Cache::get('finance_bot:thread_id');

        return $cached !== null ? (int) $cached : null;
    }

    /**
     * @return array<string, mixed>|true|null Telegram javobidagi `result`
     */
    public function call(string $method, array $params = [], bool $throw = false): mixed
    {
        if (! $this->enabled()) {
            return null;
        }

        $params = array_filter($params, fn ($v) => $v !== null);
        foreach (['reply_markup', 'link_preview_options', 'reply_parameters', 'allowed_updates'] as $jsonKey) {
            if (isset($params[$jsonKey]) && is_array($params[$jsonKey])) {
                $params[$jsonKey] = json_encode($params[$jsonKey], JSON_UNESCAPED_UNICODE);
            }
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = Http::asForm()
                ->timeout(12)
                ->post("https://api.telegram.org/bot{$this->token()}/{$method}", $params);

            $data = $response->json() ?? [];
            if (($data['ok'] ?? false) === true) {
                return $data['result'] ?? true;
            }

            // Telegram 429 (Rate limit)
            $retry = (int) ($data['parameters']['retry_after'] ?? 0);
            if ($response->status() === 429 && $retry > 0 && $retry <= 30 && $attempt < 3) {
                sleep($retry);
                continue;
            }

            $description = (string) ($data['description'] ?? $response->body());
            if (str_contains($description, 'message is not modified')) {
                return true;
            }

            Log::warning('FinanceBot: Telegram xatosi', [
                'method' => $method,
                'status' => $response->status(),
                'description' => $description,
            ]);

            if ($throw) {
                throw new RuntimeException("Telegram FinanceBot: {$description}");
            }

            return null;
        }

        return null;
    }

    /**
     * Xabar yuborish
     */
    public function sendMessage(int|string $chatId, string $text, array $extra = []): ?array
    {
        $params = array_merge([
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ], $extra);

        $result = $this->call('sendMessage', $params);

        return is_array($result) ? $result : null;
    }

    /**
     * Asosiy moliya guruhiga xabar yuborish
     */
    public function sendToGroup(string $text, array $extra = []): ?array
    {
        $chatId = $this->defaultChatId();
        if (! $chatId) {
            Log::warning('FinanceBot: Guruh chat_id sozlanmagan');
            return null;
        }

        if ($this->defaultThreadId() && ! isset($extra['message_thread_id'])) {
            $extra['message_thread_id'] = $this->defaultThreadId();
        }

        return $this->sendMessage($chatId, $text, $extra);
    }

    public function username(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        return Cache::remember('finance_bot:username', now()->addDay(), function () {
            $me = $this->call('getMe');

            return is_array($me) ? ($me['username'] ?? null) : null;
        });
    }

    public function setWebhook(string $url, string $secret = ''): bool
    {
        $params = [
            'url'             => $url,
            'allowed_updates' => ['message'],
        ];

        if ($secret !== '') {
            $params['secret_token'] = $secret;
        }

        return (bool) $this->call('setWebhook', $params, true);
    }

    public function deleteWebhook(): bool
    {
        return (bool) $this->call('deleteWebhook', [], true);
    }

    public function getWebhookInfo(): ?array
    {
        $res = $this->call('getWebhookInfo');

        return is_array($res) ? $res : null;
    }
}

