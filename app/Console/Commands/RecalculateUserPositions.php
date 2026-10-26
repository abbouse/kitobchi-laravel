<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserPositionService;
use Illuminate\Console\Command;

class RecalculateUserPositions extends Command
{
    protected $signature = 'users:recalculate-positions {--chunk=200}';

    protected $description = 'Kitobxon darajalari va klub nishonlarini qayta hisoblaydi';

    public function handle(UserPositionService $positionService): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $upgraded = 0;

        User::query()
            ->orderBy('id')
            ->chunk($chunk, function ($users) use ($positionService, &$upgraded) {
                foreach ($users as $user) {
                    // Daraja bir chaqiruvda aniq qiymatga o'rnatiladi, nishonlar ham beriladi
                    if ($positionService->evaluateAndPromote($user, 'backfill_recalculation')) {
                        $upgraded++;
                    }
                }
            });

        $this->info("Position recalculation tugadi. Upgrade count: {$upgraded}");

        return self::SUCCESS;
    }
}
