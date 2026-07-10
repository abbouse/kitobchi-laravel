<?php

namespace App\Jobs;

use App\Models\Sold;
use App\Services\PaylovFiscalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Karta to'lovi muvaffaqiyatli bo'lgach buyurtma uchun OFD fiskal chek
 * yaratadi. Queued — to'lov oqimini sekinlashtirmaydi, OFD vaqtincha
 * ishlamasa retry qiladi.
 */
class RegisterOrderFiscalReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    /** @var array<int, int> retry oralig'i (sekund) */
    public array $backoff = [60, 300];

    public function __construct(
        public readonly int $orderId,
    ) {
    }

    public function handle(PaylovFiscalizationService $service): void
    {
        if (! $service->isEnabled()) {
            return;
        }

        $order = Sold::query()->find($this->orderId);
        if (! $order) {
            return;
        }

        $service->registerForOrder($order);
    }
}
