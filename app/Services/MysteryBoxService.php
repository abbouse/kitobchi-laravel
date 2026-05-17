<?php

namespace App\Services;

use App\Models\Books;
use App\Models\MysteryBoxDelivery;
use App\Models\MysteryBoxSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MysteryBoxService
{
    public function activate(int $subId): bool
    {
        $sub = MysteryBoxSubscription::query()->find($subId);

        if (!$sub) {
            Log::error("[MysteryBoxService] Obuna topilmadi: #{$subId}");
            return false;
        }

        if ($sub->status === MysteryBoxSubscription::STATUS_ACTIVE) {
            $this->ensureDeliverySchedule($sub->fresh(['plan', 'deliveries']));
            $this->syncSubscriptionProgress($sub->fresh('deliveries'));

            return true;
        }

        if (in_array($sub->status, [
            MysteryBoxSubscription::STATUS_CANCELLED,
            MysteryBoxSubscription::STATUS_PAUSED,
            MysteryBoxSubscription::STATUS_COMPLETED,
        ], true)) {
            Log::warning("[MysteryBoxService] Bekor/tugagan obunani aktivlab bo'lmaydi: #{$subId}");
            return false;
        }

        if ($sub->status !== MysteryBoxSubscription::STATUS_PENDING) {
            Log::warning("[MysteryBoxService] Noto'g'ri status: #{$subId} status={$sub->status}");
            return false;
        }

        DB::transaction(function () use ($subId) {
            /** @var MysteryBoxSubscription $subscription */
            $subscription = MysteryBoxSubscription::query()
                ->with(['plan', 'deliveries'])
                ->lockForUpdate()
                ->findOrFail($subId);

            $startedAt = $subscription->started_at?->copy() ?? now();

            $subscription->update([
                'status' => MysteryBoxSubscription::STATUS_ACTIVE,
                'paid_at' => now(),
                'started_at' => $startedAt,
                'ends_at' => $startedAt->copy()->addMonths($subscription->total_months)->endOfDay(),
                'preferred_dispatch_type' => $this->dispatchTypeForSubscription($subscription),
                'assignment_meta' => array_merge(
                    is_array($subscription->assignment_meta) ? $subscription->assignment_meta : [],
                    [
                        'auto_assigned' => true,
                        'assigned_at' => now()->toIso8601String(),
                        'engine' => 'mystery-box-v2',
                    ]
                ),
            ]);

            $this->ensureDeliverySchedule($subscription->fresh(['plan', 'deliveries']));
            $this->syncSubscriptionProgress($subscription->fresh('deliveries'));
        });

        Log::info("[MysteryBoxService] Aktivlashtirildi va barcha oylar taqsimlandi: sub#{$subId}");

        return true;
    }

    public function cancelPayment(int $subId): bool
    {
        $affected = DB::table('mystery_box_subscriptions')
            ->where('id', $subId)
            ->where('status', MysteryBoxSubscription::STATUS_PENDING)
            ->update([
                'status' => MysteryBoxSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            MysteryBoxDelivery::query()
                ->where('subscription_id', $subId)
                ->whereIn('status', [MysteryBoxDelivery::STATUS_PENDING, MysteryBoxDelivery::STATUS_PREPARING])
                ->delete();
        }

        Log::info("[MysteryBoxService] payment_cancelled: #{$subId}", ['affected' => $affected]);

        return $affected > 0;
    }

    public function ensureDeliverySchedule(MysteryBoxSubscription $subscription): void
    {
        $subscription->loadMissing(['plan', 'deliveries']);

        $catalog = $this->eligibleBooksCatalog();
        $existing = $subscription->deliveries->keyBy('month_number');
        $usedBookIds = $subscription->deliveries
            ->sortBy('month_number')
            ->flatMap(fn (MysteryBoxDelivery $delivery) => $delivery->book_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        for ($month = 1; $month <= (int) $subscription->total_months; $month++) {
            /** @var MysteryBoxDelivery|null $delivery */
            $delivery = $existing->get($month);
            $dispatchType = $delivery?->dispatch_type ?: $this->dispatchTypeForSubscription($subscription);
            $plannedFor = $this->plannedDateForMonth($subscription, $month);

            $selectionMode = $delivery?->selection_mode ?? 'auto';
            $bookIds = is_array($delivery?->book_ids) ? array_values(array_filter(array_map('intval', $delivery->book_ids))) : [];

            if ($selectionMode !== 'manual' && count($bookIds) !== (int) $subscription->books_per_month) {
                $bookIds = $this->selectBooksForMonth($catalog, $subscription, $month, $usedBookIds);
                $selectionMode = 'auto';
            }

            $selectionMeta = array_merge(
                is_array($delivery?->selection_meta) ? $delivery->selection_meta : [],
                [
                    'seed' => "mbx:{$subscription->id}:{$month}",
                    'engine' => 'mystery-box-v2',
                    'selection_mode' => $selectionMode,
                    'selected_at' => now()->toIso8601String(),
                ]
            );

            $payload = [
                'dispatch_type' => $dispatchType,
                'book_ids' => $bookIds,
                'selection_mode' => $selectionMode,
                'selection_meta' => $selectionMeta,
                'planned_for_date' => $plannedFor->toDateString(),
            ];

            if ($delivery) {
                $mutableStatuses = [
                    MysteryBoxDelivery::STATUS_PENDING,
                    MysteryBoxDelivery::STATUS_PREPARING,
                    MysteryBoxDelivery::STATUS_READY_TO_SHIP,
                ];

                if (in_array($delivery->status, $mutableStatuses, true)) {
                    $delivery->fill($payload);
                    $delivery->save();
                }
            } else {
                $subscription->deliveries()->create(array_merge($payload, [
                    'month_number' => $month,
                    'status' => MysteryBoxDelivery::STATUS_PENDING,
                ]));
            }

            $usedBookIds = array_values(array_unique(array_merge($usedBookIds, $bookIds)));
        }
    }

    public function syncSubscriptionProgress(MysteryBoxSubscription $subscription): MysteryBoxSubscription
    {
        $subscription->loadMissing('deliveries');

        $receivedCount = $subscription->deliveries
            ->where('status', MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED)
            ->count();

        /** @var MysteryBoxDelivery|null $nextDelivery */
        $nextDelivery = $subscription->deliveries
            ->reject(fn (MysteryBoxDelivery $delivery) => $delivery->is_final)
            ->sortBy('month_number')
            ->first();

        $status = $subscription->status;
        if ($receivedCount >= (int) $subscription->total_months && (int) $subscription->total_months > 0) {
            $status = MysteryBoxSubscription::STATUS_COMPLETED;
        } elseif ($status === MysteryBoxSubscription::STATUS_COMPLETED && $receivedCount < (int) $subscription->total_months) {
            $status = MysteryBoxSubscription::STATUS_ACTIVE;
        }

        $subscription->update([
            'delivered_months' => $receivedCount,
            'next_delivery_at' => $nextDelivery?->planned_for_date
                ? Carbon::parse($nextDelivery->planned_for_date)->startOfDay()
                : null,
            'status' => $status,
        ]);

        return $subscription->fresh(['plan', 'deliveries']);
    }

    public function transitionDelivery(
        MysteryBoxDelivery $delivery,
        string $targetStatus,
        ?string $trackingNote = null,
    ): MysteryBoxDelivery {
        $allowed = $this->allowedStatusesForDispatchType((string) $delivery->dispatch_type);

        if (!in_array($targetStatus, $allowed, true)) {
            throw new RuntimeException('Bu yetkazish turi uchun status noto‘g‘ri.');
        }

        return DB::transaction(function () use ($delivery, $targetStatus, $trackingNote) {
            /** @var MysteryBoxDelivery $lockedDelivery */
            $lockedDelivery = MysteryBoxDelivery::query()
                ->with('subscription')
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            $payload = ['status' => $targetStatus];

            if ($trackingNote !== null) {
                $payload['tracking_note'] = trim($trackingNote) !== '' ? trim($trackingNote) : $lockedDelivery->tracking_note;
            }

            switch ($targetStatus) {
                case MysteryBoxDelivery::STATUS_PREPARING:
                    $payload['prepared_at'] = $lockedDelivery->prepared_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_READY_TO_SHIP:
                    $payload['prepared_at'] = $lockedDelivery->prepared_at ?? now();
                    $payload['ready_at'] = $lockedDelivery->ready_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_SHIPPED:
                    $payload['prepared_at'] = $lockedDelivery->prepared_at ?? now();
                    $payload['ready_at'] = $lockedDelivery->ready_at ?? now();
                    $payload['shipped_at'] = $lockedDelivery->shipped_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_ARRIVED_TO_POST:
                    $payload['shipped_at'] = $lockedDelivery->shipped_at ?? now();
                    $payload['arrived_to_post_at'] = $lockedDelivery->arrived_to_post_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_OUT_FOR_DELIVERY:
                    $payload['shipped_at'] = $lockedDelivery->shipped_at ?? now();
                    $payload['out_for_delivery_at'] = $lockedDelivery->out_for_delivery_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_DELIVERED:
                    $payload['delivered_at'] = $lockedDelivery->delivered_at ?? now();
                    break;
                case MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED:
                    $payload['customer_received_at'] = $lockedDelivery->customer_received_at ?? now();
                    if ($lockedDelivery->dispatch_type === MysteryBoxDelivery::DISPATCH_COURIER) {
                        $payload['out_for_delivery_at'] = $lockedDelivery->out_for_delivery_at ?? now();
                    } else {
                        $payload['delivered_at'] = $lockedDelivery->delivered_at ?? now();
                    }
                    break;
                case MysteryBoxDelivery::STATUS_CANCELLED:
                    $payload['cancelled_at'] = $lockedDelivery->cancelled_at ?? now();
                    break;
            }

            $lockedDelivery->update($payload);
            $this->syncSubscriptionProgress($lockedDelivery->subscription);

            return $lockedDelivery->fresh('subscription');
        });
    }

    public function dispatchTypeForSubscription(MysteryBoxSubscription $subscription): string
    {
        $preferred = (string) ($subscription->preferred_dispatch_type ?? '');
        if (in_array($preferred, [
            MysteryBoxDelivery::DISPATCH_COURIER,
            MysteryBoxDelivery::DISPATCH_POSTAL,
            MysteryBoxDelivery::DISPATCH_PICKUP,
        ], true)) {
            return $preferred;
        }

        $address = is_array($subscription->address) ? $subscription->address : [];
        $hint = strtolower((string) ($address['deliveryType'] ?? $address['type'] ?? ''));

        return match (true) {
            str_contains($hint, 'post') || str_contains($hint, 'pochta') => MysteryBoxDelivery::DISPATCH_POSTAL,
            str_contains($hint, 'pickup') || str_contains($hint, 'pick') || str_contains($hint, 'instore') => MysteryBoxDelivery::DISPATCH_PICKUP,
            default => MysteryBoxDelivery::DISPATCH_COURIER,
        };
    }

    public function allowedStatusesForDispatchType(string $dispatchType): array
    {
        return match ($dispatchType) {
            MysteryBoxDelivery::DISPATCH_POSTAL => [
                MysteryBoxDelivery::STATUS_PENDING,
                MysteryBoxDelivery::STATUS_PREPARING,
                MysteryBoxDelivery::STATUS_READY_TO_SHIP,
                MysteryBoxDelivery::STATUS_SHIPPED,
                MysteryBoxDelivery::STATUS_ARRIVED_TO_POST,
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED,
                MysteryBoxDelivery::STATUS_CANCELLED,
            ],
            MysteryBoxDelivery::DISPATCH_PICKUP => [
                MysteryBoxDelivery::STATUS_PENDING,
                MysteryBoxDelivery::STATUS_PREPARING,
                MysteryBoxDelivery::STATUS_READY_TO_SHIP,
                MysteryBoxDelivery::STATUS_DELIVERED,
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED,
                MysteryBoxDelivery::STATUS_CANCELLED,
            ],
            default => [
                MysteryBoxDelivery::STATUS_PENDING,
                MysteryBoxDelivery::STATUS_PREPARING,
                MysteryBoxDelivery::STATUS_READY_TO_SHIP,
                MysteryBoxDelivery::STATUS_SHIPPED,
                MysteryBoxDelivery::STATUS_OUT_FOR_DELIVERY,
                MysteryBoxDelivery::STATUS_CUSTOMER_RECEIVED,
                MysteryBoxDelivery::STATUS_CANCELLED,
            ],
        };
    }

    public function opsQueueQuery()
    {
        return MysteryBoxDelivery::query()
            ->with(['subscription.user:id,name,lastname,phone_number', 'subscription.plan:id,name_uz,months'])
            ->whereNotIn('status', MysteryBoxDelivery::FINAL_STATUSES)
            ->whereDate('planned_for_date', '<=', now()->toDateString())
            ->orderBy('planned_for_date')
            ->orderBy('month_number');
    }

    private function plannedDateForMonth(MysteryBoxSubscription $subscription, int $month): Carbon
    {
        $start = $subscription->started_at?->copy() ?? $subscription->created_at?->copy() ?? now();

        return $start->copy()->addMonths(max(0, $month - 1))->startOfDay();
    }

    private function eligibleBooksCatalog(): Collection
    {
        /** @var EloquentCollection<int, Books> $books */
        $books = Books::query()
            ->where('is_hidden', 0)
            ->where('is_approved', 1)
            ->where('status', true)
            ->where('count', '>', 0)
            ->get(['id', 'name', 'author', 'price', 'count', 'totalSales']);

        if ($books->isEmpty()) {
            return collect();
        }

        $prices = $books->pluck('price')->map(fn ($value) => max(1, (int) $value));
        $sales = $books->pluck('totalSales')->map(fn ($value) => max(0, (int) $value));
        $stocks = $books->pluck('count')->map(fn ($value) => max(1, (int) $value));

        $minPrice = (int) $prices->min();
        $maxPrice = (int) $prices->max();
        $minSales = (int) $sales->min();
        $maxSales = (int) $sales->max();
        $maxStock = max(1, (int) $stocks->max());

        return $books->map(function (Books $book) use ($minPrice, $maxPrice, $minSales, $maxSales, $maxStock) {
            $price = max(1, (int) $book->price);
            $sales = max(0, (int) ($book->totalSales ?? 0));
            $stock = max(1, (int) ($book->count ?? 1));

            $priceNorm = $maxPrice === $minPrice ? 1.0 : 1 - (($price - $minPrice) / max(1, $maxPrice - $minPrice));
            $salesNorm = $maxSales === $minSales ? 0.5 : (($sales - $minSales) / max(1, $maxSales - $minSales));
            $stockNorm = min(1, $stock / $maxStock);

            return [
                'id' => (int) $book->id,
                'name' => (string) $book->name,
                'price' => $price,
                'popular_score' => ($salesNorm * 0.72) + ($stockNorm * 0.18) + ($priceNorm * 0.10),
                'value_score' => ($priceNorm * 0.54) + ($salesNorm * 0.26) + ($stockNorm * 0.20),
                'mixed_score' => ($salesNorm * 0.42) + ($priceNorm * 0.33) + ($stockNorm * 0.25),
            ];
        })->values();
    }

    private function selectBooksForMonth(
        Collection $catalog,
        MysteryBoxSubscription $subscription,
        int $month,
        array $usedBookIds,
    ): array {
        $needed = max(1, (int) $subscription->books_per_month);
        if ($catalog->isEmpty()) {
            return [];
        }

        $selected = [];
        $metrics = ['popular_score', 'value_score', 'mixed_score'];

        for ($slot = 0; $slot < $needed; $slot++) {
            $metric = $metrics[min($slot, count($metrics) - 1)];
            $pool = $catalog
                ->reject(fn (array $book) => in_array($book['id'], $selected, true))
                ->reject(function (array $book) use ($usedBookIds, $catalog, $selected, $needed) {
                    $remainingUnique = $catalog
                        ->reject(fn (array $row) => in_array($row['id'], array_merge($usedBookIds, $selected), true))
                        ->count();

                    return $remainingUnique >= ($needed - count($selected))
                        && in_array($book['id'], $usedBookIds, true);
                })
                ->sortByDesc(fn (array $book) => $book[$metric] + $this->seedNoise($subscription->id, $month, $slot, $book['id']))
                ->values();

            if ($pool->isEmpty()) {
                $pool = $catalog
                    ->reject(fn (array $book) => in_array($book['id'], $selected, true))
                    ->sortByDesc(fn (array $book) => $book[$metric] + $this->seedNoise($subscription->id, $month, $slot, $book['id']))
                    ->values();
            }

            if ($pool->isEmpty()) {
                break;
            }

            $selected[] = (int) $pool->first()['id'];
        }

        return array_values($selected);
    }

    private function seedNoise(int $subscriptionId, int $month, int $slot, int $bookId): float
    {
        $hash = crc32("mbx:{$subscriptionId}:{$month}:{$slot}:{$bookId}");

        return ($hash % 1000) / 10000;
    }
}
