<?php

namespace App\Console\Commands;

use App\Models\Sold;
use App\Models\User;
use App\Services\CashbackNotificationService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleasePendingCashback extends Command
{
    protected $signature = 'cashback:release-pending';
    protected $description = '7 kun kutgan cashbacklarni foydalanuvchilarga tasdiqlab beradi';

    public function __construct(
        private readonly OrderService $orderService,
        private readonly CashbackNotificationService $cashbackNotificationService,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $orders = Sold::query()
            ->where('paymentStatus', 2)
            ->where('status', 'C')
            ->where('is_instore', false)
            ->whereNotNull('cashback_ready_at')
            ->where('cashback_ready_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('awarded_cashback_amount')
                    ->orWhere('awarded_cashback_amount', '<=', 0);
            })
            ->orderBy('cashback_ready_at')
            ->get();

        $released = 0;
        $releasedByUser = [];

        foreach ($orders as $order) {
            try {
                $amount = $this->orderService->releaseScheduledCashback($order, notify: false);
                if ($amount > 0) {
                    $released++;
                    $releasedByUser[(int) $order->user_id]['amount'] = (($releasedByUser[(int) $order->user_id]['amount'] ?? 0) + $amount);
                    $releasedByUser[(int) $order->user_id]['orders'][] = (int) $order->id;
                }
            } catch (\Throwable $e) {
                Log::error('cashback:release-pending failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($releasedByUser as $userId => $payload) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $orderIds = array_values(array_unique($payload['orders'] ?? []));
            if (empty($orderIds)) {
                continue;
            }

            $sent = $this->cashbackNotificationService->sendAwardedBatch(
                $user,
                (int) ($payload['amount'] ?? 0),
                count($orderIds),
            );

            if ($sent) {
                DB::table('solds')
                    ->whereIn('id', $orderIds)
                    ->update([
                        'cashback_notified_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }

        $this->info("Released cashback orders: {$released}");

        return self::SUCCESS;
    }
}
