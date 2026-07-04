<?php

namespace App\Console\Commands;

use App\Models\SplitContract;
use App\Models\SplitInstallment;
use App\Services\SplitContractService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CollectSplitInstallments extends Command
{
    protected $signature = 'split:collect-installments {--limit=200 : Bir yurishda maksimal urinishlar soni}';

    protected $description = "Muddati kelgan split installmentlarni saqlangan Paylov kartalardan avto yechadi (retry: due, +1, +3, +7 kun)";

    public function handle(SplitContractService $service): int
    {
        if (! Schema::hasTable('split_installments')) {
            $this->info('split_installments jadvali mavjud emas.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $now = now();

        $due = SplitInstallment::query()
            ->where('status', SplitInstallment::STATUS_PENDING)
            ->where('is_upfront', false)
            ->where(function ($query) use ($now) {
                $query->where(function ($fresh) use ($now) {
                    // Birinchi urinish: muddat kelgan va hali urinilmagan.
                    $fresh->where('attempt_count', 0)->where('due_at', '<=', $now);
                })->orWhere(function ($retry) use ($now) {
                    // Qayta urinish: retry vaqti kelgan.
                    $retry->where('attempt_count', '>', 0)
                        ->whereNotNull('next_attempt_at')
                        ->where('next_attempt_at', '<=', $now);
                });
            })
            ->whereHas('contract', function ($query) {
                $query->whereIn('status', [SplitContract::STATUS_ACTIVE, SplitContract::STATUS_OVERDUE]);
            })
            ->orderBy('due_at')
            ->limit($limit)
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($due as $installment) {
            try {
                $service->chargeDueInstallment($installment) ? $ok++ : $failed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Installment #{$installment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Yechildi: {$ok}, muvaffaqiyatsiz: {$failed}, jami ko'rildi: {$due->count()}");

        return self::SUCCESS;
    }
}
