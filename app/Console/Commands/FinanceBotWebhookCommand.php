<?php

namespace App\Console\Commands;

use App\Services\FinanceBot\FinanceBotClient;
use Illuminate\Console\Command;

/**
 * Moliya boti webhookini o'rnatadi: php artisan finance-bot:webhook (yoki --delete / --info)
 */
class FinanceBotWebhookCommand extends Command
{
    protected $signature = 'finance-bot:webhook 
                            {--delete : Webhookni o\'chirish}
                            {--info : Webhook holatini ko\'rish}';

    protected $description = "Moliya / Chiqimlar boti webhookini o'rnatadi yoki holatini ko'rsatadi";

    public function handle(FinanceBotClient $tg): int
    {
        if (! $tg->enabled()) {
            $this->error('FINANCE_BOT_TOKEN .env da yo\'q yoki FINANCE_BOT_ENABLED=false.');

            return self::FAILURE;
        }

        if ($this->option('info')) {
            $info = $tg->getWebhookInfo();
            $this->info('Webhook info: ' . json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($this->option('delete')) {
            $tg->deleteWebhook();
            $this->info("Webhook muvaffaqiyatli o'chirildi.");

            return self::SUCCESS;
        }

        $secret = (string) config('services.finance_bot.webhook_secret', '');
        $url = route('telegram.finance-webhook');

        $tg->setWebhook($url, $secret);

        // Ortiqcha komandalar kerak emas, menyuni tozalaymiz
        $tg->call('deleteMyCommands');

        $this->info("FinanceBot webhook o'rnatildi: {$url}");
        $this->line('Bot username: @' . ($tg->username() ?? '?'));

        return self::SUCCESS;
    }
}

