<?php

namespace App\Console\Commands;

use App\Enums\OrderStatusCode;
use App\Enums\SellerOrderStatusCode;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerTransaction;
use App\Services\SellerOrderSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileLegacySellerCredits extends Command
{
    protected $signature = 'seller:reconcile-legacy-credits {--dry-run : Faqat tekshiradi, balansni o\'zgartirmaydi}';

    protected $description = 'Eski model bo\'yicha kuryerga topshirilganda seller balansiga erta tushgan faol buyurtmalarni reconcile qiladi.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = SellerOrder::query()
            ->select(['seller_orders.id', 'seller_orders.seller_id', 'seller_orders.order_id', 'seller_orders.amount'])
            ->join('solds', 'solds.id', '=', 'seller_orders.order_id')
            ->where(function ($query) {
                $query->where('seller_orders.status_code', SellerOrderStatusCode::HANDED_TO_COURIER->value)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('seller_orders.status_code')
                            ->where('seller_orders.status', SellerOrderStatusCode::HANDED_TO_COURIER->legacy());
                    });
            })
            ->where(function ($query) {
                $query->whereNotIn('solds.status_code', [
                    OrderStatusCode::DELIVERED->value,
                    OrderStatusCode::CUSTOMER_RECEIVED->value,
                    OrderStatusCode::CANCELLED->value,
                    OrderStatusCode::RETURNED->value,
                ])->orWhere(function ($fallback) {
                    $fallback->whereNull('solds.status_code')
                        ->whereNotIn('solds.status', ['C', 'D', 'F']);
                });
            })
            ->where('seller_orders.amount', '>', 0)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('seller_transactions')
                    ->whereColumn('seller_transactions.seller_order_id', 'seller_orders.id')
                    ->where('seller_transactions.category', SellerOrderSettlementService::CATEGORY_LEGACY_INFLIGHT_ADJUSTMENT);
            })
            ->orderBy('seller_orders.id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Reconcile qilinadigan faol legacy seller order topilmadi.');
            return self::SUCCESS;
        }

        $this->info("Topildi: {$rows->count()} ta seller order.");

        foreach ($rows as $row) {
            $this->line("#{$row->id} | seller={$row->seller_id} | order={$row->order_id} | amount={$row->amount}");

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($row) {
                $seller = Seller::query()->lockForUpdate()->find($row->seller_id);
                if (!$seller) {
                    return;
                }

                $alreadyAdjusted = SellerTransaction::query()
                    ->where('seller_order_id', $row->id)
                    ->where('category', SellerOrderSettlementService::CATEGORY_LEGACY_INFLIGHT_ADJUSTMENT)
                    ->exists();

                if ($alreadyAdjusted) {
                    return;
                }

                $amount = (int) $row->amount;

                SellerTransaction::create([
                    'seller_id' => (int) $seller->id,
                    'order_id' => (int) $row->order_id,
                    'seller_order_id' => (int) $row->id,
                    'type' => 'expense',
                    'category' => SellerOrderSettlementService::CATEGORY_LEGACY_INFLIGHT_ADJUSTMENT,
                    'card' => null,
                    'amount' => $amount,
                    'commissionPercent' => 0,
                    'commissionPrice' => 0,
                    'netAmount' => $amount,
                    'status' => SellerTransaction::STATUS_APPROVED,
                    'description' => "Legacy balance adjustment for in-flight order #{$row->order_id}",
                ]);

                $seller->balance = (int) $seller->balance - $amount;
                $seller->save();
            });
        }

        $this->info($dryRun
            ? 'Dry-run tugadi. Hech qanday balans o‘zgartirilmadi.'
            : 'Legacy seller balance reconcile yakunlandi.');

        return self::SUCCESS;
    }
}
