<?php

namespace App\Console\Commands;

use App\Models\ProjectSetting;
use App\Models\SplitUserProfile;
use App\Services\SplitPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Haftalik promo: bo'sh nasiya limiti bor mijozlarga "limitingizni
 * ishlating" push yuboradi.
 *
 * Qoidalar:
 *  - faqat eligible profillar (blok/overdue/default avtomatik chetda)
 *  - bo'sh limit kamida minimal buyurtma summasiga yetishi kerak
 *  - bir mijozga 7 kunda ko'pi bilan 1 marta (last_promo_push_at)
 *  - push yuborilgandagina timestamp yangilanadi (token yo'q bo'lsa
 *    keyingi haftada yana urinadi)
 */
class SendSplitLimitPromos extends Command
{
    protected $signature = 'split:send-limit-promos {--limit=1000 : Bir yurishda maksimal push soni}';

    protected $description = "Bo'sh nasiya limiti bor mijozlarga haftalik eslatma push yuboradi";

    public function handle(SplitPushService $pushService): int
    {
        if (! Schema::hasTable('split_user_profiles')) {
            $this->info('split_user_profiles jadvali yo\'q — o\'tkazib yuborildi.');

            return self::SUCCESS;
        }

        $settings = ProjectSetting::query()->first();

        if (! ($settings?->split_enabled ?? false) || ! ($settings?->split_public_enabled ?? false)) {
            $this->info('Split o\'chirilgan — promo yuborilmaydi.');

            return self::SUCCESS;
        }

        // Bo'sh limit kamida minimal buyurtma summasiga yetsin —
        // aks holda mijoz baribir hech narsa ololmaydi
        $minAvailable = max(50_000, (int) ($settings->split_global_min_order_sum ?? 100_000));

        $maxSends = max(1, (int) $this->option('limit'));
        $sent = 0;
        $skipped = 0;

        SplitUserProfile::query()
            ->with('user:id,locale')
            ->where('eligible', true)
            ->where('available_limit', '>=', $minAvailable)
            ->where(function ($query) {
                $query->whereNull('last_promo_push_at')
                    ->orWhere('last_promo_push_at', '<=', now()->subDays(6));
            })
            ->orderBy('id')
            ->chunkById(200, function ($profiles) use ($pushService, &$sent, &$skipped, $maxSends) {
                foreach ($profiles as $profile) {
                    if ($sent >= $maxSends) {
                        return false;
                    }

                    if (! $profile->user) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $ok = $pushService->sendLimitPromo($profile->user, (int) $profile->available_limit);
                    } catch (\Throwable $e) {
                        Log::warning('[Split] Promo push failed', [
                            'user_id' => $profile->user_id,
                            'error' => $e->getMessage(),
                        ]);
                        $ok = false;
                    }

                    if ($ok) {
                        $profile->forceFill(['last_promo_push_at' => now()])->save();
                        $sent++;
                    } else {
                        $skipped++;
                    }
                }

                return true;
            });

        $this->info("Promo push: {$sent} ta yuborildi, {$skipped} ta o'tkazib yuborildi.");

        return self::SUCCESS;
    }
}
