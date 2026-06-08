<?php

namespace App\Services;

use App\Models\OrderItemFinancialSnapshot;
use App\Models\SellerOrderItem;
use App\Models\Sold;
use Illuminate\Support\Collection;

class OrderFinancialSnapshotService
{
    public function ensureSnapshotsForOrder(Sold $order): void
    {
        $order = $order->fresh();
        if (! $order) {
            return;
        }

        $items = SellerOrderItem::query()
            ->join('seller_orders', 'seller_orders.id', '=', 'seller_order_items.order_id')
            ->where('seller_orders.order_id', $order->id)
            ->select([
                'seller_order_items.*',
                'seller_orders.order_id as sold_id',
            ])
            ->get()
            ->map(function ($row) {
                $item = new SellerOrderItem((array) $row);
                $item->exists = true;
                $item->id = (int) $row->id;
                $item->order_id = (int) $row->order_id;
                $item->seller_id = (int) $row->seller_id;
                $item->product_id = (int) $row->product_id;
                $item->variant_id = $row->variant_id ? (int) $row->variant_id : null;
                $item->quantity = (int) $row->quantity;
                $item->price = (int) $row->price;

                return $item;
            });

        if ($items->isEmpty()) {
            return;
        }

        $existing = OrderItemFinancialSnapshot::query()
            ->whereIn('seller_order_item_id', $items->pluck('id')->all())
            ->pluck('seller_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = $items->reject(fn (SellerOrderItem $item) => in_array((int) $item->id, $existing, true))->values();
        if ($missing->isEmpty()) {
            return;
        }

        $allocations = $this->allocateForItems(
            items: $missing,
            promoTotal: (int) ($order->discountAmount ?? 0),
            cashbackTotal: (int) ($order->cashbackAmount ?? 0),
            giftCertTotal: (int) ($order->giftCertAmount ?? 0),
        );

        foreach ($missing as $item) {
            $allocated = $allocations->get((int) $item->id, [
                'gross_amount' => 0,
                'promo_allocated' => 0,
                'cashback_allocated' => 0,
                'gift_cert_allocated' => 0,
                'card_paid_allocated' => 0,
            ]);

            OrderItemFinancialSnapshot::query()->updateOrCreate(
                ['seller_order_item_id' => (int) $item->id],
                [
                    'sold_id' => $order->id,
                    'seller_order_id' => (int) $item->order_id,
                    'seller_id' => (int) $item->seller_id,
                    'product_id' => (int) $item->product_id,
                    'variant_id' => $item->variant_id ? (int) $item->variant_id : null,
                    'product_type' => (string) $item->type,
                    'quantity' => (int) $item->quantity,
                    'gross_amount' => (int) $allocated['gross_amount'],
                    'promo_allocated' => (int) $allocated['promo_allocated'],
                    'cashback_allocated' => (int) $allocated['cashback_allocated'],
                    'gift_cert_allocated' => (int) $allocated['gift_cert_allocated'],
                    'card_paid_allocated' => (int) $allocated['card_paid_allocated'],
                    'meta' => [
                        'snapshotted_at' => now()->toIso8601String(),
                    ],
                ]
            );
        }
    }

    /**
     * @param Collection<int, SellerOrderItem> $items
     * @return Collection<int, array{gross_amount:int,promo_allocated:int,cashback_allocated:int,gift_cert_allocated:int,card_paid_allocated:int}>
     */
    private function allocateForItems(Collection $items, int $promoTotal, int $cashbackTotal, int $giftCertTotal): Collection
    {
        $rows = $items->map(function (SellerOrderItem $item) {
            return [
                'id' => (int) $item->id,
                'type' => (string) $item->type,
                'gross_amount' => max(0, (int) $item->price * max(1, (int) $item->quantity)),
            ];
        });

        $eligible = $rows->reject(fn (array $row) => $row['type'] === 'gift' || $row['gross_amount'] <= 0)->values();
        $eligibleTotal = (int) $eligible->sum('gross_amount');

        $promoAlloc = $this->proportionalAllocate($eligible, $promoTotal, $eligibleTotal);
        $cashbackAlloc = $this->proportionalAllocate($eligible, $cashbackTotal, $eligibleTotal);
        $giftAlloc = $this->proportionalAllocate($eligible, $giftCertTotal, $eligibleTotal);

        return $rows->mapWithKeys(function (array $row) use ($promoAlloc, $cashbackAlloc, $giftAlloc) {
            $promo = (int) ($promoAlloc[$row['id']] ?? 0);
            $cashback = (int) ($cashbackAlloc[$row['id']] ?? 0);
            $gift = (int) ($giftAlloc[$row['id']] ?? 0);
            $cardPaid = max(0, (int) $row['gross_amount'] - $promo - $cashback - $gift);

            return [
                (int) $row['id'] => [
                    'gross_amount' => (int) $row['gross_amount'],
                    'promo_allocated' => $promo,
                    'cashback_allocated' => $cashback,
                    'gift_cert_allocated' => $gift,
                    'card_paid_allocated' => $cardPaid,
                ],
            ];
        });
    }

    /**
     * @param Collection<int, array{id:int,gross_amount:int}> $rows
     * @return array<int,int>
     */
    private function proportionalAllocate(Collection $rows, int $total, int $grossTotal): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['id']] = 0;
        }

        if ($total <= 0 || $grossTotal <= 0 || $rows->isEmpty()) {
            return $result;
        }

        $remainders = [];
        $allocated = 0;

        foreach ($rows as $row) {
            $share = ($total * (int) $row['gross_amount']) / $grossTotal;
            $floor = (int) floor($share);
            $result[(int) $row['id']] = $floor;
            $allocated += $floor;
            $remainders[] = [
                'id' => (int) $row['id'],
                'remainder' => $share - $floor,
            ];
        }

        $left = $total - $allocated;
        usort($remainders, fn (array $a, array $b) => $b['remainder'] <=> $a['remainder']);

        foreach ($remainders as $remainder) {
            if ($left <= 0) {
                break;
            }

            $result[$remainder['id']]++;
            $left--;
        }

        return $result;
    }
}
