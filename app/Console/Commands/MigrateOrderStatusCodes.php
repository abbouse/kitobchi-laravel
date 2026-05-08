<?php

namespace App\Console\Commands;

use App\Enums\CourierOrderStatusCode;
use App\Enums\OrderKind;
use App\Enums\OrderStatusCode;
use App\Enums\PaymentStatusCode;
use App\Enums\PostalReturnStatus;
use App\Enums\SellerOrderStatusCode;
use App\Models\CourierOrder;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOrderStatusCodes extends Command
{
    protected $signature = 'orders:migrate-status-codes {--dry-run : Show what would change without writing}';
    protected $description = 'Backfill canonical string status code columns for solds, seller_orders, and courier_orders.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Dry run: order status codes backfill preview started.'
            : 'Order status codes backfill started.');

        $counts = [
            'solds' => 0,
            'seller_orders' => 0,
            'courier_orders' => 0,
        ];

        $runner = function () use (&$counts, $dryRun) {
            Sold::query()->orderBy('id')->chunkById(200, function ($orders) use (&$counts, $dryRun) {
                foreach ($orders as $order) {
                    $payload = [
                        'status_code' => OrderStatusCode::fromLegacy($order->status)->value,
                        'payment_status_code' => PaymentStatusCode::fromLegacy($order->paymentStatus)->value,
                        'order_kind' => $order->order_kind ?: OrderKind::STANDARD->value,
                        'postal_return_status' => $order->postal_return_status ?: PostalReturnStatus::NONE->value,
                    ];

                    if (!$dryRun) {
                        $order->forceFill($payload)->save();
                    }
                    $counts['solds']++;
                }
            });

            SellerOrder::query()->orderBy('id')->chunkById(200, function ($orders) use (&$counts, $dryRun) {
                foreach ($orders as $order) {
                    $statusCode = SellerOrderStatusCode::fromLegacy($order->status)->value;
                    if (!$dryRun) {
                        $order->forceFill(['status_code' => $statusCode])->save();
                    }
                    $counts['seller_orders']++;
                }
            });

            CourierOrder::query()->orderBy('id')->chunkById(200, function ($orders) use (&$counts, $dryRun) {
                foreach ($orders as $order) {
                    $statusCode = CourierOrderStatusCode::fromLegacy($order->status)->value;
                    if (!$dryRun) {
                        $order->forceFill(['status_code' => $statusCode])->save();
                    }
                    $counts['courier_orders']++;
                }
            });
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $this->table(['Table', 'Rows'], [
            ['solds', $counts['solds']],
            ['seller_orders', $counts['seller_orders']],
            ['courier_orders', $counts['courier_orders']],
        ]);

        $this->info($dryRun
            ? 'Dry run completed. No rows were changed.'
            : 'Order status codes backfill completed.');

        return self::SUCCESS;
    }
}
