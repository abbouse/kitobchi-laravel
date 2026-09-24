<?php

namespace App\Console\Commands;

use App\Services\Catalog\CatalogSlotService;
use Illuminate\Console\Command;

class CatalogSlotsExpire extends Command
{
    protected $signature = 'catalog:slots-expire';

    protected $description = 'Muddati tugagan katalog joylarini yopadi va kartalarni qayta hisoblaydi';

    public function handle(CatalogSlotService $slots): int
    {
        $count = $slots->expireDue();
        $this->info("Yopilgan joylar: {$count} ta.");

        return self::SUCCESS;
    }
}
