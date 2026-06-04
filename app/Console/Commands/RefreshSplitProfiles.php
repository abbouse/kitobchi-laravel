<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SplitProfileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshSplitProfiles extends Command
{
    protected $signature = 'split:refresh-user-profiles
        {--user_id= : Faqat bitta user uchun qayta hisoblaydi}
        {--dry-run : Natijani ko\'rsatadi, saqlamaydi}';

    protected $description = 'Split eligibility, confidence va limit profilini qayta hisoblaydi.';

    public function handle(SplitProfileService $service): int
    {
        $userId = $this->option('user_id');
        $dryRun = (bool) $this->option('dry-run');

        try {
            if ($userId) {
                $user = User::query()->findOrFail((int) $userId);
                $result = $service->refreshUser($user, ! $dryRun);

                $this->table(
                    ['User', 'Eligible', 'Score', 'Limit', 'Available'],
                    [[
                        $user->id,
                        $result['eligible'] ? 'yes' : 'no',
                        number_format((float) $result['confidence_score'], 2),
                        number_format((float) $result['computed_limit'], 0, '.', ' '),
                        number_format((float) $result['available_limit'], 0, '.', ' '),
                    ]]
                );

                if (! empty($result['eligibility_reasons'])) {
                    $this->line('Reasons: '.implode(' | ', $result['eligibility_reasons']));
                }

                $this->info($dryRun
                    ? 'Dry-run tugadi, saqlanmadi.'
                    : 'Split profili yangilandi.');

                return self::SUCCESS;
            }

            $count = $service->refreshAll(null, ! $dryRun);
            $this->info($dryRun
                ? "{$count} ta user split profili hisoblandi, lekin saqlanmadi."
                : "{$count} ta user split profili yangilandi.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('split:refresh-user-profiles', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
