<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProductPersonalizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RefreshUserInterestProfiles extends Command
{
    protected $signature = 'products:refresh-user-interests
        {--user_id= : Faqat bitta user profilini hisoblaydi}
        {--limit=1000 : Bir ishga tushishda maksimal user soni}
        {--days=90 : Qiziqish profiliga olinadigan view tarixi kuni}
        {--cleanup-guest-days=45 : Login qilmagan guest view loglari TTL kuni}
        {--dry-run : Hisoblaydi, lekin saqlamaydi}';

    protected $description = 'Product view loglardan user qiziqish profilini yangilaydi va eski guest view loglarni tozalaydi.';

    public function handle(ProductPersonalizationService $service): int
    {
        if (! Schema::hasTable('product_view_logs')) {
            $this->warn('product_view_logs jadvali topilmadi.');
            return self::SUCCESS;
        }

        if (! Schema::hasTable('user_interest_profiles')) {
            $this->warn('user_interest_profiles jadvali topilmadi. Avval migrate qiling.');
            return self::SUCCESS;
        }

        $userId = $this->option('user_id');
        $limit = max(1, (int) $this->option('limit'));
        $days = max(7, (int) $this->option('days'));
        $guestCleanupDays = max(7, (int) $this->option('cleanup-guest-days'));
        $dryRun = (bool) $this->option('dry-run');

        try {
            if ($userId) {
                $user = User::query()->findOrFail((int) $userId);
                $payload = $service->refreshUserInterestProfile($user, $days, ! $dryRun);

                $this->table(
                    ['User', 'Views', 'Confidence', 'Summary'],
                    [[
                        $user->id,
                        $payload['views_count'],
                        number_format((float) $payload['confidence_score'], 2),
                        $payload['profile']['summary'] ?? '—',
                    ]]
                );

                $this->info($dryRun ? 'Dry-run tugadi, profil saqlanmadi.' : 'User qiziqish profili yangilandi.');
                return self::SUCCESS;
            }

            $updated = $service->refreshUsers($limit, $days, ! $dryRun);
            $deletedGuests = $dryRun ? 0 : $service->cleanupGuestViews($guestCleanupDays);

            $this->info($dryRun
                ? "{$updated} ta user qiziqish profili hisoblandi, lekin saqlanmadi."
                : "{$updated} ta user qiziqish profili yangilandi.");

            if (! $dryRun) {
                $this->info("{$deletedGuests} ta eski guest product view log tozalandi.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('products:refresh-user-interests', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
