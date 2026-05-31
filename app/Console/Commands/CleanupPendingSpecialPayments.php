<?php

namespace App\Console\Commands;

use App\Models\GiftCertificate;
use App\Models\MysteryBoxSubscription;
use App\Services\GiftCertService;
use App\Services\MysteryBoxService;
use Illuminate\Console\Command;

class CleanupPendingSpecialPayments extends Command
{
    protected $signature = 'shop:cleanup-pending-special-payments
                            {--minutes=30 : Necha daqiqadan keyin pending_payment bekor qilinsin}
                            {--purge-days=30 : Bekor qilingan yozuvlar necha kundan keyin o\'chirilsin}
                            {--dry-run : Hech narsani o\'zgartirmaydi, faqat ko\'rsatadi}';

    protected $description = 'Gift certificate va mystery boxdagi eski pending_payment yozuvlarini avtomatik bekor qiladi';

    public function __construct(
        private readonly GiftCertService $giftCertService,
        private readonly MysteryBoxService $mysteryBoxService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMinutes($minutes);
        $purgeCutoff = now()->subDays(max(1, (int) $this->option('purge-days')));

        $pendingCertificates = GiftCertificate::query()
            ->where('status', GiftCertificate::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->get(['id', 'code', 'buyer_user_id', 'nominal_uzs', 'created_at']);

        $pendingSubscriptions = MysteryBoxSubscription::query()
            ->where('status', MysteryBoxSubscription::STATUS_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->get(['id', 'user_id', 'plan_id', 'price_uzs', 'created_at']);

        $this->info("Cutoff: {$cutoff->format('Y-m-d H:i:s')} ({$minutes} daqiqa)");
        $this->line("Gift certificates: {$pendingCertificates->count()} ta");
        $this->line("Mystery box subscriptions: {$pendingSubscriptions->count()} ta");
        $cancelledCertificates = GiftCertificate::query()
            ->where('status', GiftCertificate::STATUS_CANCELLED)
            ->where('updated_at', '<=', $purgeCutoff);
        $cancelledSubscriptions = MysteryBoxSubscription::query()
            ->where('status', MysteryBoxSubscription::STATUS_CANCELLED)
            ->where('updated_at', '<=', $purgeCutoff);
        $this->line("Purge gift certificates: {$cancelledCertificates->count()} ta");
        $this->line("Purge mystery subscriptions: {$cancelledSubscriptions->count()} ta");

        if ($dryRun) {
            if ($pendingCertificates->isNotEmpty()) {
                $this->newLine();
                $this->table(
                    ['GiftCert ID', 'Code', 'Buyer', 'Amount', 'Created'],
                    $pendingCertificates->map(fn (GiftCertificate $cert) => [
                        $cert->id,
                        $cert->code,
                        $cert->buyer_user_id,
                        $cert->nominal_uzs,
                        optional($cert->created_at)->format('Y-m-d H:i:s'),
                    ])->all()
                );
            }

            if ($pendingSubscriptions->isNotEmpty()) {
                $this->newLine();
                $this->table(
                    ['Sub ID', 'User', 'Plan', 'Amount', 'Created'],
                    $pendingSubscriptions->map(fn (MysteryBoxSubscription $sub) => [
                        $sub->id,
                        $sub->user_id,
                        $sub->plan_id,
                        $sub->price_uzs,
                        optional($sub->created_at)->format('Y-m-d H:i:s'),
                    ])->all()
                );
            }

            $this->warn('Dry-run rejimi: hech narsa o‘zgartirilmadi.');
            return self::SUCCESS;
        }

        $giftCancelled = 0;
        foreach ($pendingCertificates as $cert) {
            if ($this->giftCertService->cancelPayment((int) $cert->id)) {
                $giftCancelled++;
            }
        }

        $mysteryCancelled = 0;
        foreach ($pendingSubscriptions as $sub) {
            if ($this->mysteryBoxService->cancelPayment((int) $sub->id)) {
                $mysteryCancelled++;
            }
        }

        $this->info("Cancelled gift certificates: {$giftCancelled}");
        $this->info("Cancelled mystery subscriptions: {$mysteryCancelled}");
        $giftDeleted = $cancelledCertificates->delete();
        $mysteryDeleted = 0;
        $cancelledSubscriptions->eachById(function (MysteryBoxSubscription $subscription) use (&$mysteryDeleted) {
            $subscription->deliveries()->delete();
            $subscription->delete();
            $mysteryDeleted++;
        });
        $this->info("Deleted old cancelled gift certificates: {$giftDeleted}");
        $this->info("Deleted old cancelled mystery subscriptions: {$mysteryDeleted}");

        return self::SUCCESS;
    }
}
