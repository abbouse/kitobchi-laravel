<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class SetTelegramWebhook extends Command
{
    protected $signature   = 'bot:webhook {action=set : set | delete | info}';
    protected $description = 'Telegram bot webhook ni boshqarish';

    public function handle(Nutgram $bot): int
    {
        $action = $this->argument('action');
        $url    = config('nutgram.webhook.url');
        $secret = config('nutgram.webhook.secret_token');

        match ($action) {
            'set' => $this->setWebhook($bot, $url, $secret),
            'delete' => $this->deleteWebhook($bot),
            'info'   => $this->webhookInfo($bot),
            default  => $this->error("Noto'g'ri action: $action"),
        };

        return self::SUCCESS;
    }

    private function setWebhook(Nutgram $bot, ?string $url, ?string $secret): void
    {
        $url = $url ?: (string) config('nutgram.webhook.url');
        if (empty($url)) {
            $this->error("Webhook URL sozlanmagan.");
            return;
        }

        $result = $bot->setWebhook($url, secret_token: $secret);

        if ($result) {
            $this->info("✅ Webhook o'rnatildi: $url");
        } else {
            $this->error("❌ Webhook o'rnatilmadi");
        }
    }

    private function deleteWebhook(Nutgram $bot): void
    {
        $bot->deleteWebhook();
        $this->info("✅ Webhook o'chirildi");
    }

    private function webhookInfo(Nutgram $bot): void
    {
        $info = $bot->getWebhookInfo();
        $this->table(
            ['Parameter', 'Value'],
            [
                ['URL',             $info->url ?? '-'],
                ['Has custom cert', $info->has_custom_certificate ? 'yes' : 'no'],
                ['Pending updates', $info->pending_update_count ?? 0],
                ['Last error',      $info->last_error_message ?? '-'],
            ]
        );
    }
}