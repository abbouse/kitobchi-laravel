<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\PaylovFiscalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Har bir Paylov to'lovi o'zining alohida OFD chekiga ega. */
class RegisterTransactionFiscalReceiptJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    public int $uniqueFor = 600;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly int $transactionId,
    ) {}

    public function handle(PaylovFiscalizationService $service): void
    {
        if (! $service->isEnabled()) {
            return;
        }

        $transaction = Transaction::query()->with('order')->find($this->transactionId);
        if (! $transaction) {
            return;
        }

        $service->registerForTransaction($transaction, $transaction->order);
    }

    public function uniqueId(): string
    {
        return (string) $this->transactionId;
    }
}
