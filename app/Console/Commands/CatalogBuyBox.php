<?php

namespace App\Console\Commands;

use App\Services\Catalog\BuyBoxService;
use Illuminate\Console\Command;

class CatalogBuyBox extends Command
{
    protected $signature = 'catalog:buybox {--edition= : Faqat bitta karta}';

    protected $description = "Katalog kartalarining ro'yxatdagi taklifini (buy box) qayta hisoblaydi";

    public function handle(BuyBoxService $buyBox): int
    {
        if ($id = $this->option('edition')) {
            $this->line(json_encode($buyBox->recompute((int) $id)));

            return self::SUCCESS;
        }

        $count = $buyBox->recomputeAll();
        $this->info("Qayta hisoblandi: {$count} ta karta.");

        return self::SUCCESS;
    }
}
