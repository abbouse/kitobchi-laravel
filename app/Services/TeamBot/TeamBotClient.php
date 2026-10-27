<?php

namespace App\Services\TeamBot;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Jamoa boti uchun yengil Telegram Bot API mijozi.
 *
 * Mijozlar support boti (Nutgram, TELEGRAM_TOKEN) bilan aralashmaydi — alohida
 * bot va alohida token (TEAM_BOT_TOKEN). Token faqat .env'da saqlanadi.
 */
class TeamBotClient
{
    public function enabled(): bool
    {
        return (bool) config('services.team_bot.enabled') && $this->token() !== '';
    }

    public function token(): string
    {
        return (string) config('services.team_bot.token', '');
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

            // Telegram limit (429): aytilgan vaqt kutiladi va qayta uriniladi
            $retry = (int) ($data['parameters']['retry_after'] ?? 0);
            if ($response->status() === 429 && $retry > 0 && $retry <= 30 && $attempt < 3) {
                sleep($retry);
                continue;
            }

            $description = (string) ($data['description'] ?? $response->body());
            // Matn o'zgarmagan bo'lsa Telegram xato qaytaradi — bu xato emas
            if (str_contains($description, 'message is not modified')) {
                return true;
            }

            Log::warning('TeamBot: Telegram xatosi', ['method' => $method, 'status' => $response->status(), 'description' => $description]);
            if ($throw) {
                throw new RuntimeException("Telegram: {$description}");
            }

            return null;
        }

        return null;
    }

    public function username(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        return Cache::remember('team_bot:username', now()->addDay(), function () {
            $me = $this->call('getMe');

            return is_array($me) ? ($me['username'] ?? null) : null;
        });
    }
}
