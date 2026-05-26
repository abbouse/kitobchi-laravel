<?php

namespace App\Jobs;

use App\Services\ProductVectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductVectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 3;

    public function __construct(
        public readonly string $productType,
        public readonly int $productId,
    ) {
    }

    public function handle(ProductVectorService $service): void
    {
        $service->syncByTypeAndId($this->productType, $this->productId);
    }
}
