<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierOrderItem;
use App\Models\Couriers;
use App\Models\CourierTask;
use App\Models\CourierTransaction;
use App\Models\OrderFulfillment;
use App\Models\Sold;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourierTaskOrchestratorService
{
    public function __construct(
        private readonly CourierCashOnDeliveryCapacityService $codCapacityService,
        private readonly CourierTaskPayoutService $payoutService,
        private readonly OrderRealtimeService $orderRealtimeService,
    ) {}

    public function ensureTasksForOrder(Sold $order): Collection
    {
        $order->loadMissing('fulfillment.hub');
        /** @var OrderFulfillment|null $fulfillment */
        $fulfillment = $order->fulfillment;
        if (! $fulfillment) {
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

        return CourierTask::query()
            ->where('order_id', $order->id)
            ->get();
    }

    public function acceptAvailableTasksForCourier(Sold $order, Couriers $courier, ?array $eligibleTaskIds = null): Collection
    {
        $order->loadMissing('fulfillment');
        $targetLegs = $this->targetLegsForCurrentPhase($order->fulfillment);

        $tasks = $this->ensureTasksForOrder($order)
            ->filter(function (CourierTask $task) {
                return $task->status_code === CourierTaskStatusCode::ASSIGNED->value
                    && $task->courier_id === null;
            })
            ->when($targetLegs !== [], fn (Collection $tasks) => $tasks->whereIn('leg', $targetLegs))
            ->when(is_array($eligibleTaskIds), fn (Collection $tasks) => $tasks->whereIn('id', $eligibleTaskIds))
            ->values();

        if ($tasks->isEmpty()) {
            return collect();
        }

        DB::transaction(function () use ($tasks, $courier) {
            $totalCodExposure = $tasks
                ->filter(fn (CourierTask $task) => $task->is_cod)
                ->sum(fn (CourierTask $task) => (int) ($task->cash_collect_amount ?? 0));

            $lockedCourier = Couriers::query()->lockForUpdate()->findOrFail($courier->id);
            if ($totalCodExposure > 0 && ! $this->codCapacityService->canTakeCashOrder($lockedCourier, $totalCodExposure)) {
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

    public function targetLegsForCurrentPhase(?OrderFulfillment $fulfillment): array
    {
        if (! $fulfillment) {
            return [];
        }

        if ($fulfillment->fulfillment_mode === FulfillmentMode::DIRECT_COURIER->value) {
            return [CourierTaskLeg::DIRECT_DELIVERY->value];
        }

        if ($fulfillment->fulfillment_mode === FulfillmentMode::POSTAL_ONLY_VIA_HUB->value) {
            return [CourierTaskLeg::FIRST_MILE->value];
        }

        if ($fulfillment->fulfillment_mode === FulfillmentMode::HUB_BASED->value) {
            $lastMileStatuses = [
                FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
                FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            ];

            return in_array($fulfillment->status_code, $lastMileStatuses, true)
                ? [CourierTaskLeg::LAST_MILE->value]
                : [CourierTaskLeg::FIRST_MILE->value];
        }

        return [];
    }

    /**
     * Buyurtmaning ochiq topshiriqlarini bekor qiladi va har biri uchun
     * kuryerning naqd (COD) bandligini bo'shatadi. Avval ommaviy UPDATE
     * ishlatilib, band qilingan summa kuryerda abadiy "osilib" qolardi.
     */
    public function cancelOpenTasks(Sold $order): void
    {
        $tasks = CourierTask::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status_code', [
                CourierTaskStatusCode::COMPLETED->value,
                CourierTaskStatusCode::CANCELLED->value,
                CourierTaskStatusCode::FAILED->value,
            ])
            ->get();

        foreach ($tasks as $task) {
            if ($task->is_cod && $task->cod_reserved_at && ! $task->cod_released_at) {
                $this->codCapacityService->releaseReservation($task);
                $task->refresh();
            }
            $task->forceFill(['status_code' => CourierTaskStatusCode::CANCELLED->value])->save();
        }
    }

    /**
     * Kuryerni buyurtmadan bo'shatadi: uning (hali olib ketilmagan)
     * topshiriqlari ochiq holatga qaytadi, COD bandligi bo'shaydi.
     *
     * @return Collection<int, CourierTask> bo'shatilgan topshiriqlar
     *
     * @throws \RuntimeException kuryer mahsulotni allaqachon olib ketgan bo'lsa
     */
    public function releaseCourierTasks(Sold $order, int $courierId, bool $allowPickedUp = false, array $audit = []): Collection
    {
        return DB::transaction(function () use ($order, $courierId, $allowPickedUp, $audit) {
            $tasks = CourierTask::query()
                ->where('order_id', $order->id)
                ->where('courier_id', $courierId)
                ->whereIn('status_code', [
                    CourierTaskStatusCode::ACCEPTED->value,
                    CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                    CourierTaskStatusCode::PICKED_UP->value,
                ])
                ->lockForUpdate()
                ->get();

            if (! $allowPickedUp && $tasks->contains(fn (CourierTask $t) => $t->status_code === CourierTaskStatusCode::PICKED_UP->value)) {
                throw new \RuntimeException("Buyurtma allaqachon olib ketilgan — uni bo'shatib bo'lmaydi.");
            }

            foreach ($tasks as $task) {
                if ($task->is_cod && $task->cod_reserved_at && ! $task->cod_released_at) {
                    $this->codCapacityService->releaseReservation($task);
                    $task->refresh();
                }

                $history = $task->meta['releases'] ?? [];
                $history[] = array_merge([
                    'courier_id' => $courierId,
                    'status' => $task->status_code,
                    'at' => now()->toIso8601String(),
                ], $audit);
                $task->meta = array_merge($task->meta ?? [], ['releases' => array_slice($history, -10)]);
                $this->resetToOpen($task);
                $task->save();
            }

            return $tasks;
        });
    }

    private function resetToOpen(CourierTask $task): void
    {
        $task->courier_id = null;
        $task->status_code = CourierTaskStatusCode::ASSIGNED->value;
        $task->assigned_at = now();
        $task->accepted_at = null;
        $task->arrived_at = null;
        $task->picked_up_at = null;
        $task->dropped_off_at = null;
        $task->completed_at = null;
        $task->cod_reserved_at = null;
        $task->cod_released_at = null;
    }

    public function markSellerHandover(Sold $order, ?int $sellerId = null, ?int $courierId = null): void
    {
        $order->loadMissing('fulfillment');
        $fulfillment = $order->fulfillment;
        if (! $fulfillment) {
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

        // Ko'p do'konli buyurtma: hamma do'kon topshirmaguncha bosqich
        // oldinga siljimaydi; hub allaqachon qabul qilgan bo'lsa ortga ham
        // qaytmaydi (avval ikkinchi do'kon skanerlaganda holat
        // "do'kondan olindi"ga qaytib qolardi).
        $stillWaiting = CourierTask::query()
            ->where('order_id', $order->id)
            ->whereIn('leg', [CourierTaskLeg::FIRST_MILE->value, CourierTaskLeg::DIRECT_DELIVERY->value])
            ->whereIn('status_code', [
                CourierTaskStatusCode::ASSIGNED->value,
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
            ])
            ->exists();
        $earlyStatuses = [
            null,
            FulfillmentStatusCode::AWAITING_SELLER_PREP->value,
            FulfillmentStatusCode::READY_FOR_PICKUP->value,
        ];
        if ($stillWaiting || ! in_array($fulfillment->status_code, $earlyStatuses, true)) {
            return;
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

        if (! $task) {
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

    public function markArrivedAtHub(OrderFulfillment $fulfillment): void
    {
        $fulfillment->loadMissing('order');
        $order = $fulfillment->order;
        if (! $order) {
            return;
        }

        // Kitob hubda — do'konlar topshirgan hisoblanadi. Aks holda keyin
        // "yuborish"da buyurtma "yetkazilmoqda"ga o'tmay qolardi.
        \App\Models\SellerOrder::query()
            ->where('order_id', $order->id)
            ->whereIn('status_code', [
                \App\Enums\SellerOrderStatusCode::NEW->value,
                \App\Enums\SellerOrderStatusCode::ACCEPTED->value,
            ])
            ->update([
                'status' => \App\Enums\SellerOrderStatusCode::HANDED_TO_COURIER->legacy(),
                'status_code' => \App\Enums\SellerOrderStatusCode::HANDED_TO_COURIER->value,
                'updated_at' => now(),
            ]);

        // Hech kim olmagan first-mile topshiriqlar (kitobni do'kon o'zi
        // olib kelgan) endi kerak emas — kuryer ro'yxatida qolib ketmasin
        CourierTask::query()
            ->where('order_id', $order->id)
            ->where('leg', CourierTaskLeg::FIRST_MILE->value)
            ->whereNull('courier_id')
            ->where('status_code', CourierTaskStatusCode::ASSIGNED->value)
            ->update(['status_code' => CourierTaskStatusCode::CANCELLED->value, 'updated_at' => now()]);
        CourierOrder::query()
            ->where('order_id', $order->id)
            ->whereNull('courier_id')
            ->where('status_code', CourierOrderStatusCode::PENDING->value)
            ->update([
                'status' => CourierOrderStatusCode::CANCELLED->legacy(),
                'status_code' => CourierOrderStatusCode::CANCELLED->value,
                'updated_at' => now(),
            ]);

        $tasks = CourierTask::query()
            ->where('order_id', $order->id)
            ->where('fulfillment_id', $fulfillment->id)
            ->where('leg', CourierTaskLeg::FIRST_MILE->value)
            ->whereNotNull('courier_id')
            ->whereIn('status_code', [
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
            ])
            ->get();

        foreach ($tasks as $task) {
            // First-mile kuryer mijoz pulini olmaydi. Eski noto'g'ri COD
            // snapshot qolgan bo'lsa, reservationni yechib tashlaymiz.
            if ($task->is_cod) {
                $this->codCapacityService->releaseReservation($task);
                $task->refresh();
            }

            DB::transaction(function () use ($task) {
                $lockedTask = CourierTask::query()->lockForUpdate()->findOrFail($task->id);
                if ($lockedTask->settled_at) {
                    return;
                }

                $lockedTask->status_code = CourierTaskStatusCode::COMPLETED->value;
                $lockedTask->is_cod = false;
                $lockedTask->cash_collect_amount = 0;
                $lockedTask->dropped_off_at ??= now();
                $lockedTask->completed_at ??= now();

                $payout = max(0, (int) ($lockedTask->fee_amount ?? 0));
                if ($payout > 0 && $lockedTask->courier_id) {
                    $courier = Couriers::query()->lockForUpdate()->find($lockedTask->courier_id);
                    if ($courier) {
                        $courier->balance = (int) $courier->balance + $payout;
                        $courier->save();

                        CourierTransaction::query()->firstOrCreate(
                            [
                                'courier_id' => $courier->id,
                                'category' => 'hub_delivery',
                                'order_id' => $lockedTask->order_id,
                                'courier_task_id' => $lockedTask->id,
                            ],
                            [
                                'card' => '',
                                'type' => 'income',
                                'amount' => $payout,
                                'commissionPercent' => 0,
                                'commissionPrice' => 0,
                                'netAmount' => $payout,
                                'status' => 'approved',
                                'description' => "Buyurtma #{$lockedTask->order_id} hubgacha yetkazildi",
                            ]
                        );
                    }
                }

                $lockedTask->settlement_status = $payout > 0 ? 'paid' : 'zero';
                $lockedTask->settled_at = now();
                $lockedTask->save();

                CourierOrder::query()
                    ->where('order_id', $lockedTask->order_id)
                    ->where('courier_id', $lockedTask->courier_id)
                    ->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                    ->update([
                        'status' => CourierOrderStatusCode::DELIVERED->legacy(),
                        'status_code' => CourierOrderStatusCode::DELIVERED->value,
                        'updated_at' => now(),
                    ]);
            });

            // Kuryer ilovasiga darhol signal. Buni qo'shmasak, hub o'zi
            // "qabul qildim" bosganda kuryerga hech qanday xabar bormaydi va
            // buyurtma uning ekranida "Yo'lda" bo'lib qotib qoladi.
            $courierOrder = CourierOrder::query()
                ->where('order_id', $task->order_id)
                ->where('courier_id', $task->courier_id)
                ->latest('updated_at')
                ->first();

            if ($courierOrder) {
                $this->orderRealtimeService->broadcastCourierOrderUpdated(
                    $courierOrder,
                    'courier_order.hub_delivered'
                );
            }
        }
    }

    /**
     * Hub kitobni last-mile kuryerga topshirdi: topshiriq "olindi",
     * fulfillment "yetkazilmoqda".
     */
    public function markLastMilePickedUp(OrderFulfillment $fulfillment): ?CourierTask
    {
        $task = CourierTask::query()
            ->where('order_id', $fulfillment->order_id)
            ->where('fulfillment_id', $fulfillment->id)
            ->where('leg', CourierTaskLeg::LAST_MILE->value)
            ->whereNotNull('courier_id')
            ->whereIn('status_code', [
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
            ])
            ->latest('id')
            ->first();

        if ($task) {
            $task->forceFill([
                'status_code' => CourierTaskStatusCode::PICKED_UP->value,
                'picked_up_at' => now(),
            ])->save();
        }

        return $task;
    }

    public function ensureLastMileTask(OrderFulfillment $fulfillment): ?CourierTask
    {
        $fulfillment->loadMissing('order', 'hub');
        $order = $fulfillment->order;
        if (! $order || ! $fulfillment->hub) {
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

        $task = $this->firstOrCreateTask(
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

        $courierOrder = CourierOrder::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'courier_id' => null,
                'status_code' => CourierOrderStatusCode::PENDING->value,
            ],
            [
                'user_id' => (int) $order->user_id,
                'amount' => (int) ($order->amount ?? 0),
                'status' => CourierOrderStatusCode::PENDING->legacy(),
                'courierPrice' => max(0, (int) $task->fee_amount - (int) $task->bonus_amount),
                'courierBonus' => (int) $task->bonus_amount,
            ]
        );

        // Yangi qator yaratilsa, CourierOrderObserver o'zi xabar beradi.
        // Ammo eski egasiz `pending` qator qayta ishlatilsa observer ishlamaydi
        // va kuryerlar yangi hub->mijoz buyurtmasini ko'rmay qoladi.
        if (! $courierOrder->wasRecentlyCreated && empty($courierOrder->courier_id)) {
            $this->orderRealtimeService->broadcastCourierOrderUpdated(
                $courierOrder,
                'courier_order.available'
            );
        }

        return $task;
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
        $task = CourierTask::query()->firstOrNew([
            'order_id' => $order->id,
            'fulfillment_id' => $fulfillment->id,
            'seller_id' => $sellerId,
            'leg' => $leg->value,
        ]);

        $revive = $task->exists && $task->status_code === CourierTaskStatusCode::CANCELLED->value;
        $open = ! $task->exists || $revive || (
            $task->status_code === CourierTaskStatusCode::ASSIGNED->value && $task->courier_id === null
        );

        // Kuryer qabul qilgan (yoki yakunlangan) topshiriq qayta yozilmaydi:
        // avval har GET so'rovda COD summasi va to'lov qayta hisoblanib,
        // band qilingan summa bilan bo'shatiladigan summa farq qilib qolardi.
        if (! $open) {
            return $task;
        }

        $task->hub_id = $fulfillment->hub_id;
        $task->pickup_address = $pickupAddress;
        $task->dropoff_address = $dropoffAddress;
        $task->is_cod = $isCod;
        $task->cash_collect_amount = $isCod ? $cashCollectAmount : 0;
        $this->payoutService->apply($task);
        $task->meta = array_merge($task->meta ?? [], [
            'generated_from_fulfillment' => true,
            'payout_model' => 'km_based',
            'legacy_requested_fee_amount' => $feeAmount,
        ]);

        if (! $task->exists || $revive) {
            $this->resetToOpen($task);
        }

        if ($task->isDirty()) {
            $task->save();
        }

        return $task;
    }

    private function normalizeAddress(mixed $address): ?array
    {
        if (! is_array($address) || empty($address)) {
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
