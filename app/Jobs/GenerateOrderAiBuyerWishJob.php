<?php

namespace App\Jobs;

use App\Models\Sold;
use App\Services\AiBuyerWishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Buyurtma berilgandan 30 daqiqa o'tgach, agar xaridor o'zi tilak yozmagan bo'lsa
 * va buyurtma hali yo'lga chiqmagan bo'lsa (faqat yangi yoki qadoqlanmoqda),
 * OpenAI orqali shu foydalanuvchi nomidan samimiy tilak generatsiya qiladi.
 */
class GenerateOrderAiBuyerWishJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly int $orderId
    ) {}

    public function handle(AiBuyerWishService $wishService): void
    {
        $order = Sold::find($this->orderId);

        if (! $order) {
            Log::info("GenerateOrderAiBuyerWishJob: buyurtma #{$this->orderId} topilmadi.");
            return;
        }

        $wishService->generateForOrder($order);
    }
}
