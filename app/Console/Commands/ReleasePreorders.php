<?php

namespace App\Console\Commands;

use App\Enums\OrderStatusCode;
use App\Models\Sold;
use App\Services\CourierBroadcaster;
use App\Services\OrderStatusPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Jo'natish kuni kelgan predzakaz buyurtmalarini kuryerlarga chiqaradi va
 * mijozga xabar beradi. Kuniga bir necha marta ishlaydi — har buyurtma bir
 * marta xabar oladi.
 */
class ReleasePreorders extends Command
{
    protected $signature = 'orders:release-preorders';

    protected $description = "Jo'natish kuni kelgan oldindan buyurtmalarni kuryerlarga chiqaradi";

    public function handle(CourierBroadcaster $broadcaster, OrderStatusPushService $push): int
    {
        if (! Schema::hasColumn('solds', 'preorder_ships_at')) {
            return self::SUCCESS;
        }

        $count = 0;
        Sold::query()
            ->whereNotNull('preorder_ships_at')
            ->whereDate('preorder_ships_at', '<=', today())
            ->whereDate('preorder_ships_at', '>=', today()->subDays(3))
            ->whereNotIn('status_code', [
                OrderStatusCode::CANCELLED->value,
                OrderStatusCode::RETURNED->value,
                OrderStatusCode::DELIVERED->value,
                OrderStatusCode::CUSTOMER_RECEIVED->value,
            ])
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($broadcaster, $push, &$count) {
                foreach ($orders as $order) {
                    if (! Cache::add('preorder-released:'.$order->id, 1, now()->addDays(5))) {
                        continue;
                    }
                    $broadcaster->notifyPendingForOrder((int) $order->id);
                    $push->sendPreorderReleasedNotice($order);
                    $count++;
                }
            });

        $this->info("Released preorders: {$count}");

        return self::SUCCESS;
    }
}
