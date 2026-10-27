<?php

namespace App\Console\Commands;

use App\Services\TeamBot\TeamBotClient;
use Illuminate\Console\Command;

/** Jamoa boti webhookini o'rnatadi: php artisan team-bot:webhook (yoki --delete) */
class TeamBotWebhookCommand extends Command
{
    protected $signature = 'team-bot:webhook {--delete : Webhookni o\'chirish}';

    protected $description = "Jamoa Telegram boti webhooki va buyruqlar menyusini o'rnatadi";

    public function handle(TeamBotClient $tg): int
    {
        if (! $tg->enabled()) {
            $this->error('TEAM_BOT_TOKEN .env da yo\'q yoki TEAM_BOT_ENABLED=false.');

            return self::FAILURE;
        }
        if ($this->option('delete')) {
            $tg->call('deleteWebhook', [], true);
            $this->info("Webhook o'chirildi.");

            return self::SUCCESS;
        }
        $secret = (string) config('services.team_bot.webhook_secret');
        if ($secret === '') {
            $this->error('TEAM_BOT_WEBHOOK_SECRET .env da bo\'lishi shart (masalan: openssl rand -hex 24).');

            return self::FAILURE;
        }
        $url = route('telegram.team-webhook');
        $tg->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ], true);

        $commands = [
            ['command' => 'vazifa', 'description' => 'Yangi vazifa (javob qilib yozsangiz — buyurtmaga bog\'lanadi)'],
            ['command' => 'vazifalarim', 'description' => 'Menga biriktirilgan ochiq vazifalar'],
            ['command' => 'vazifalar', 'description' => 'Jamoaning ochiq vazifalari'],
            ['command' => 'setup', 'description' => 'Guruhni ulash va mavzularni yaratish (superadmin)'],
        ];
        $tg->call('setMyCommands', ['commands' => json_encode($commands, JSON_UNESCAPED_UNICODE)]);

        $this->info("Webhook o'rnatildi: {$url}");
        $this->line('Bot username: @'.($tg->username() ?? '?'));

        return self::SUCCESS;
    }
}
