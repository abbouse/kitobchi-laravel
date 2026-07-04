<?php

namespace App\Console\Commands;

use App\Models\SplitContract;
use App\Models\SplitInstallment;
use App\Services\SplitPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendSplitPaymentReminders extends Command
{
    protected $signature = 'split:send-payment-reminders {--days=2 : Muddatdan necha kun oldin eslatish}';

    protected $description = "Split to'lovidan oldin userga push eslatma yuboradi. Kredit bilan to'liq yopilgan (waived) yoki summasi 0 bo'lgan oylar uchun eslatma yuborilmaydi.";

    public function handle(SplitPushService $pushService): int
    {
        if (! Schema::hasTable('split_installments')) {
            $this->info('split_installments jadvali mavjud emas.');

            return self::SUCCESS;
        }

        $days = max(0, (int) $this->option('days'));
        $targetDay = now()->addDays($days);

        $installments = SplitInstallment::query()
            ->with(['user', 'contract'])
            ->where('status', SplitInstallment::STATUS_PENDING)
            ->where('is_upfront', false)
            ->where('attempt_count', 0)
            ->whereBetween('due_at', [$targetDay->copy()->startOfDay(), $targetDay->copy()->endOfDay()])
            ->whereHas('contract', function ($query) {
                $query->whereIn('status', [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE]);
            })
            ->orderBy('due_at')
            ->limit(500)
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            // Bir installmentga bitta eslatma yetadi.
            if (data_get($installment->meta, 'reminder_sent_at')) {
                continue;
            }

            // Summasi 0 ga tushgan yoki to'langan qismi qoplagan bo'lsa — eslatma yo'q.
            if ((int) $installment->amount - (int) $installment->paid_amount <= 0) {
                continue;
            }

            if ($pushService->sendInstallmentReminder($installment)) {
                $installment->forceFill([
                    'meta' => array_merge($installment->meta ?? [], [
                        'reminder_sent_at' => now()->toIso8601String(),
                    ]),
                ])->save();

                $sent++;
            }
        }

        $this->info("Eslatma yuborildi: {$sent} / ko'rildi: {$installments->count()}");

        return self::SUCCESS;
    }
}
