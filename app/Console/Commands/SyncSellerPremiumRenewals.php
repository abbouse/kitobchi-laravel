<?php

namespace App\Console\Commands;

use App\Services\SellerPremiumService;
use Illuminate\Console\Command;

class SyncSellerPremiumRenewals extends Command
{
    protected $signature = 'seller-premium:sync-renewals';
    protected $description = 'Seller premium obunalarini muddatiga ko‘ra yangilaydi yoki avtomatik to‘xtatadi';

    public function handle(SellerPremiumService $premiumService): int
    {
        $results = $premiumService->processDueRenewals();
        $this->info($results->count() . ' ta seller premium obuna tekshirildi.');

        return self::SUCCESS;
    }
}
