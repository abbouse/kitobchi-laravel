<?php

namespace App\Console\Commands;

use App\Services\ProductAiModerationService;
use Illuminate\Console\Command;

class ModerateProductsWithAi extends Command
{
    protected $signature = 'products:moderate-ai
        {--type=all : book|stationery|all}
        {--limit=0 : Har bir turdan bir yurishda olinadigan maksimum}
        {--all : Final qarori bor mahsulotlarni ham qayta tekshirish}';

    protected $description = 'Kitob va kanselyariyani barcha detallari hamda rasmlari bilan AI moderatsiyadan o‘tkazadi';

    public function handle(ProductAiModerationService $service): int
    {
        if (! (bool) config('product_moderation.enabled', true)) {
            $this->warn('Product AI moderation config orqali o‘chirilgan.');

            return self::SUCCESS;
        }

        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, ['all', 'book', 'books', 'stationery', 'stationeries'], true)) {
            $this->error("Noto‘g‘ri type: {$type}. book | stationery | all ishlating.");

            return self::INVALID;
        }

        $result = $service->moderate(
            $type,
            max(0, (int) $this->option('limit')),
            (bool) $this->option('all'),
        );

        $this->info(sprintf(
            'AI moderatsiya: %d tekshirildi; %d tasdiqlandi, %d rad etildi, %d admin ko‘rigiga, %d xato.',
            $result['processed'],
            $result['approved'],
            $result['rejected'],
            $result['human_review'],
            $result['failed'],
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
