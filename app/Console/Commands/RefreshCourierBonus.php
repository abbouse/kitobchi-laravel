<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshCourierBonus extends Command
{
    protected $signature = 'courier:refresh-bonus';
    protected $description = 'Legacy no-op: courier payout is km-based now.';

    public function handle(): int
    {
        $this->info('Courier surge/SLA bonus flow is disabled. Payouts are calculated from courier_tasks.');

        return self::SUCCESS;
    }
}
