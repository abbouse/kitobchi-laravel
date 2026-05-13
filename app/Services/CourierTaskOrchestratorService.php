<?php

namespace App\Services;

use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Models\CourierOrderItem;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Models\OrderFulfillment;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierTaskOrchestratorService
{
    public function __construct(
        private readonly CourierCashOnDeliveryCapacityService $codCapacityService,
    ) {}

    public function ensureTasksForOrder(Sold $order): Collection
    {
        $order->loadMissing('fulfillment.hub');
        /** @var OrderFulfillment|null $fulfillment */
        $fulfillment = $order->fulfillment;
        if (!$fulfillment) {
            return collect();
        }

        $items = CourierOrderItem::query()
            ->where('order_id', $order->id)
            ->with('sellerLocation')
            ->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $buyerAddress = $this->normalizeAddress(collect($order->address ?? [])->first());
        $hubAddress = $fulfillment->hub ? [
            'hub_id' => $fulfillment->hub->id,
            'name' => $fulfillment->hub->name,
            'fullAddress' => $fulfillment->hub->address,
            'lat' => $fulfillment->hub->lat,
            'lon' => $fulfillment->hub->lon,
            'city_name' => $fulfillment->hub->city_name,
            'region_name' => $fulfillment->hub->region_name,
            'country_code' => $fulfillment->hub->country_code,
        ] : null;

        $created = collect();
        $grouped = $items->groupBy('seller_id');

        foreach ($grouped as $sellerId => $sellerItems) {
            $sellerLocation = $sellerItems->first()?->sellerLocation;
            $pickupAddress = $sellerLocation ? [
                'seller_location_id' => $sellerLocation->id,
                'fullAddress' => $sellerLocation->fullAddress,
                'lat' => $sellerLocation->lat,
                'lon' => $sellerLocation->lon,
                'country_code' => $sellerLocation->country_code ?? 'UZ',
            ] : null;

            if ($fulfillment->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value) {
                $created->push($this->firstOrCreateTask(
                    order: $order,
                    fulfillment: $fulfillment,
                    sellerId: (int) $sellerId,
                    leg: CourierTaskLeg::DIRECT_DELIVERY,
                    pickupAddress: $pickupAddress,
                    dropoffAddress: $buyerAddress,
                    isCod: (bool) $fulfillment->is_cod,
                    cashCollectAmount: (int) ($fulfillment->cash_collect_amount ?? 0),
                    feeAmount: (int) ($order->deliveryPrice ?? 0),
                ));
                continue;
            }

            $created->push($this->firstOrCreateTask(
                order: $order,
                fulfillment: $fulfillment,
                sellerId: (int) $sellerId,
                leg: CourierTaskLeg::FIRST_MILE,
                pickupAddress: $pickupAddress,
                dropoffAddress: $hubAddress,
                isCod: false,
                cashCollectAmount: 0,
                feeAmount: 0,
            ));
        }

        return $created;
    }

    public function acceptAvailableTasksForCourier(Sold $order, Couriers $courier): Collection
    {
        $tasks = $this->ensureTasksForOrder($order)
            ->filter(function (CourierTask $task) {
                return $task->status_code === CourierTaskStatusCode::ASSIGNED->value
                    && $task->courier_id === null;
            })
            ->values();

        if ($tasks->isEmpty()) {
            return collect();
        }

        DB::transaction(function () use ($tasks, $courier) {
            $totalCodExposure = $tasks
                ->filter(fn (CourierTask $task) => $task->is_cod)
                ->sum(fn (CourierTask $task) => (int) ($task->cash_collect_amount ?? 0));

            $lockedCourier = Couriers::query()->lockForUpdate()->findOrFail($courier->id);
            if ($totalCodExposure > 0 && !$this->codCapacityService->canTakeCashOrder($lockedCourier, $totalCodExposure)) {
                throw new \RuntimeException("Kuryer balansida bu naqd buyurtma uchun yetarli collateral yo'q.");
            }

            foreach ($tasks as $task) {
                $lockedTask = CourierTask::query()->lockForUpdate()->findOrFail($task->id);
                if ($lockedTask->courier_id !== null || $lockedTask->status_code !== CourierTaskStatusCode::ASSIGNED->value) {
                    throw new \RuntimeException('Task allaqachon band qilingan.');
                }

                $lockedTask->forceFill([
                    'courier_id' => $lockedCourier->id,
                    'status_code' => CourierTaskStatusCode::ACCEPTED->value,
                    'assigned_at' => $lockedTask->assigned_at ?: now(),
                    'accepted_at' => now(),
                ])->save();

                if ($lockedTask->is_cod) {
                    $this->codCapacityService->reserveForTask($lockedTask);
                }
            }
        });

        return CourierTask::query()
            ->whereIn('id', $tasks->pluck('id'))
            ->get();
    }

    public function markSellerHandover(Sold $order, ?int $sellerId = null, ?int $courierId = null): void
    {
        $order->loadMissing('fulfillment');
        $fulfillment = $order->fulfillment;
        if (!$fulfillment) {
            return;
        }

        $taskQuery = CourierTask::query()
            ->where('order_id', $order->id)
            ->whereIn('leg', [CourierTaskLeg::FIRST_MILE->value, CourierTaskLeg::DIRECT_DELIVERY->value]);

        if ($sellerId) {
            $taskQuery->where('seller_id', $sellerId);
        }

        if ($courierId) {
            $taskQuery->where('courier_id', $courierId);
        }

        $tasks = $taskQuery->get();
        if ($tasks->isEmpty()) {
            return;
        }

        foreach ($tasks as $task) {
            $task->status_code = CourierTaskStatusCode::PICKED_UP->value;
            $task->picked_up_at = now();
            $task->save();
        }

        if ($fulfillment->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value) {
            $fulfillment->status_code = FulfillmentStatusCode::OUT_FOR_DELIVERY->value;
            $fulfillment->out_for_delivery_at ??= now();
        } else {
            $fulfillment->status_code = FulfillmentStatusCode::PICKED_FROM_SELLER->value;
            $fulfillment->picked_from_seller_at ??= now();
        }
        $fulfillment->save();
    }

    public function markDeliveredToCustomer(Sold $order, ?int $courierId = null): ?CourierTask
    {
        $task = CourierTask::query()
            ->where('order_id', $order->id)
            ->when($courierId, fn ($query) => $query->where('courier_id', $courierId))
            ->whereIn('leg', [CourierTaskLeg::DIRECT_DELIVERY->value, CourierTaskLeg::LAST_MILE->value])
            ->whereIn('status_code', [
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
            ])
            ->latest('id')
            ->first();

        if (!$task) {
            return null;
        }

        DB::transaction(function () use ($task, $order) {
            $lockedTask = CourierTask::query()->lockForUpdate()->findOrFail($task->id);
            $lockedTask->status_code = CourierTaskStatusCode::COMPLETED->value;
            $lockedTask->dropped_off_at ??= now();
            $lockedTask->completed_at ??= now();
            $lockedTask->save();

            if ($lockedTask->is_cod) {
                $this->codCapacityService->settleDeliveredTask($lockedTask);
            }

            $fulfillment = $order->fulfillment()->lockForUpdate()->first();
            if ($fulfillment) {
                $fulfillment->status_code = FulfillmentStatusCode::DELIVERED->value;
                $fulfillment->delivered_at ??= now();
                $fulfillment->save();
            }
        });

        return $task->fresh();
    }

    public function ensureLastMileTask(OrderFulfillment $fulfillment): ?CourierTask
    {
        $fulfillment->loadMissing('order', 'hub');
        $order = $fulfillment->order;
        if (!$order || !$fulfillment->hub) {
            return null;
        }

        $dropoffAddress = $this->normalizeAddress(collect($order->address ?? [])->first());
        $pickupAddress = [
            'hub_id' => $fulfillment->hub->id,
            'name' => $fulfillment->hub->name,
            'fullAddress' => $fulfillment->hub->address,
            'lat' => $fulfillment->hub->lat,
            'lon' => $fulfillment->hub->lon,
            'city_name' => $fulfillment->hub->city_name,
            'region_name' => $fulfillment->hub->region_name,
            'country_code' => $fulfillment->hub->country_code,
        ];

        return $this->firstOrCreateTask(
            order: $order,
            fulfillment: $fulfillment,
            sellerId: 0,
            leg: CourierTaskLeg::LAST_MILE,
            pickupAddress: $pickupAddress,
            dropoffAddress: $dropoffAddress,
            isCod: (bool) $fulfillment->is_cod,
            cashCollectAmount: (int) ($fulfillment->cash_collect_amount ?? 0),
            feeAmount: (int) ($order->deliveryPrice ?? 0),
        );
    }

    private function firstOrCreateTask(
        Sold $order,
        OrderFulfillment $fulfillment,
        int $sellerId,
        CourierTaskLeg $leg,
        ?array $pickupAddress,
        ?array $dropoffAddress,
        bool $isCod,
        int $cashCollectAmount,
        int $feeAmount = 0,
    ): CourierTask {
        return CourierTask::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'fulfillment_id' => $fulfillment->id,
                'seller_id' => $sellerId,
                'leg' => $leg->value,
            ],
            [
                'hub_id' => $fulfillment->hub_id,
                'status_code' => CourierTaskStatusCode::ASSIGNED->value,
                'pickup_address' => $pickupAddress,
                'dropoff_address' => $dropoffAddress,
                'is_cod' => $isCod,
                'cash_collect_amount' => $isCod ? $cashCollectAmount : 0,
                'fee_amount' => $feeAmount,
                'assigned_at' => now(),
                'meta' => [
                    'generated_from_fulfillment' => true,
                ],
            ]
        );
    }

    private function normalizeAddress(mixed $address): ?array
    {
        if (!is_array($address) || empty($address)) {
            return null;
        }

        return [
            'fullAddress' => $address['fullAddress'] ?? $address['branch_address'] ?? null,
            'lat' => $address['lat'] ?? null,
            'lon' => $address['lon'] ?? null,
            'country_code' => $address['country_code'] ?? null,
            'fullName' => $address['fullName'] ?? null,
            'phoneNumber' => $address['phoneNumber'] ?? null,
        ];
    }
}
