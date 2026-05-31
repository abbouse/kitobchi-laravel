<?php
// app/Console/Commands/ExpireGiftCertificates.php

namespace App\Console\Commands;

use App\Models\GiftCertificate;
use Illuminate\Console\Command;

class ExpireGiftCertificates extends Command
{
    protected $signature   = 'gifts:expire';
    protected $description = 'Sent bo\'lib 1 oy ichida ishlatilmagan sertifikatlarni cancelled qiladi';

    public function handle(): void
    {
        $expired = GiftCertificate::where('status', GiftCertificate::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $cert) {
            $cert->update(['status' => 'cancelled']);
            $count++;
            $this->line("  Cancelled: {$cert->code} (expires_at: {$cert->expires_at})");
        }

        // Paid bo'lib 3 kun ichida hech kim olmasa — ham expire qilish (ixtiyoriy)
        $stale = GiftCertificate::where('status', 'paid')
            ->where('paid_at', '<=', now()->subDays(90))
            ->count();

        $this->info("Expired: {$count} ta sertifikat cancelled qilindi.");
        if ($stale > 0) {
            $this->warn("{$stale} ta 'paid' sertifikat 90 kundan beri yuborilmagan — tekshiring.");
        }
    }
}
