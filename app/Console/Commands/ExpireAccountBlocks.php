<?php

namespace App\Console\Commands;

use App\Services\Trust\AccountRestrictionService;
use Illuminate\Console\Command;

class ExpireAccountBlocks extends Command
{
    protected $signature = 'accounts:expire-blocks';

    protected $description = 'Muddati tugagan vaqtinchalik bloklarni (do‘kon, kuryer, mijoz) avtomatik olib tashlaydi';

    public function handle(AccountRestrictionService $service): int
    {
        $r = $service->expireBlocks();
        $this->info("Blokdan chiqarildi — do‘kon: {$r['sellers']}, kuryer: {$r['couriers']}, mijoz: {$r['users']}");

        return self::SUCCESS;
    }
}
