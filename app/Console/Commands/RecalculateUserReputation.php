<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserReputationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecalculateUserReputation extends Command
{
    protected $signature = 'users:recalculate-reputation
        {--user_id= : Faqat bitta user uchun qayta hisoblaydi}
        {--dry-run : Natijani ko\'rsatadi, saqlamaydi}';

    protected $description = 'User reputation score va COD ishonch holatini qayta hisoblaydi.';

    public function handle(UserReputationService $service): int
    {
        $userId = $this->option('user_id');
        $dryRun = (bool) $this->option('dry-run');

        try {
            if ($userId) {
                $user = User::query()->findOrFail((int) $userId);
                $result = $service->recalculateUser($user, !$dryRun);

                $this->table(
                    ['User', 'Reputation', 'COD Allowed', 'COD Strikes'],
                    [[
                        $result['user_id'],
                        number_format((float) $result['reputation_score'], 2),
                        $result['cash_on_delivery_allowed'] ? 'yes' : 'no',
                        $result['cod_return_strikes'],
                    ]]
                );

                $this->line(json_encode($result['metrics'], JSON_UNESCAPED_UNICODE));
                $this->info($dryRun
                    ? 'Dry-run tugadi, saqlanmadi.'
                    : 'User reputatsiyasi yangilandi.');

                return self::SUCCESS;
            }

            $count = $service->recalculateAll(null, !$dryRun);
            $this->info($dryRun
                ? "{$count} ta user reputatsiyasi hisoblandi, lekin saqlanmadi."
                : "{$count} ta user reputatsiyasi yangilandi.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('users:recalculate-reputation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
