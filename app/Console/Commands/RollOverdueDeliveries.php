<?php

namespace App\Console\Commands;

use App\Support\DeliveryRescheduler;
use Illuminate\Console\Command;

class RollOverdueDeliveries extends Command
{
    protected $signature = 'orders:roll-overdue-deliveries {--no-push : Mijozlarga push yubormaslik}';

    protected $description = 'Kuni o‘tib ketgan, hali yetkazilmagan buyurtmalarni keyingi kunga ko‘chiradi';

    public function handle(): int
    {
        $moved = DeliveryRescheduler::rollOverdue(null, ! $this->option('no-push'));
        $this->info("Ko‘chirildi: {$moved} ta buyurtma");

        return self::SUCCESS;
    }
}
