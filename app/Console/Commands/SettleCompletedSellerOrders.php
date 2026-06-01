<?php

namespace App\Console\Commands;

use App\Enums\OrderKind;
use App\Enums\PaymentStatusCode;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Models\Sold;
use App\Services\SellerOrderSettlementService;
use Illuminate\Console\Command;

class SettleCompletedSellerOrders extends Command
{
    protected $signature = 'seller:settle-completed-orders {--dry-run : Faqat topilganlarni ko‘rsatadi}';

    protected $description = 'Completed + paid bo‘lgan, lekin seller settlementi yozilmagan orderlarni settlement qiladi.';

    public function handle(SellerOrderSettlementService $settlementService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = SellerOrder::query()
            ->select(['seller_orders.id', 'seller_orders.order_id', 'seller_orders.seller_id', 'seller_orders.amount'])
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->leftJoin('seller_transactions', function ($join) {
                $join->on('seller_transactions.seller_order_id', '=', 'seller_orders.id')
                    ->where('seller_transactions.category', SellerOrderSettlementService::CATEGORY_ORDER_SALE)
                    ->where('seller_transactions.status', SellerTransaction::STATUS_APPROVED);
            })
            ->whereNull('seller_transactions.id')
            ->where(function ($query) {
                $query->where('solds.order_kind', OrderKind::STANDARD->value)
                    ->orWhereNull('solds.order_kind');
            })
            ->where('solds.payment_status_code', PaymentStatusCode::PAID->value)
            ->where(function ($query) {
                $query->whereIn('solds.status_code', ['delivered', 'customer_received'])
                    ->orWhereIn('solds.status', ['C', 'D']);
            })
            ->where('seller_orders.status_code', '!=', 'cancelled')
            ->where('seller_orders.amount', '>', 0)
            ->orderBy('seller_orders.id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Settlement qilinadigan completed seller order topilmadi.');
            return self::SUCCESS;
        }

        $this->info("Topildi: {$rows->count()} ta seller order.");

        foreach ($rows as $row) {
            $this->line("#{$row->id} | order={$row->order_id} | seller={$row->seller_id} | amount={$row->amount}");

            if ($dryRun) {
                continue;
            }

            $order = Sold::find((int) $row->order_id);
            if ($order) {
                $settlementService->settleCompletedOrder($order);
            }
        }

        $this->info($dryRun ? 'Dry-run tugadi.' : 'Completed seller order settlement yakunlandi.');

        return self::SUCCESS;
    }
}
