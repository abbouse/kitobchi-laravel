<?php

namespace App\Services;

use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Enums\FulfillmentMode;
use App\Enums\FulfillmentStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Hub;
use App\Models\OrderFulfillment;
use App\Models\Sold;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FulfillmentAdminOverrideService
{
    public function __construct(
        private readonly HubAssignmentService $hubAssignmentService,
        private readonly CourierTaskOrchestratorService $courierTaskOrchestratorService,
        private readonly CourierCashOnDeliveryCapacityService $codCapacityService,
    ) {}

    public function switchMode(Sold $order, FulfillmentMode $targetMode, ?Hub $targetHub = null, ?string $note = null): OrderFulfillment
    {
        return DB::transaction(function () use ($order, $targetMode, $targetHub, $note) {
            $admin = Auth::guard('admin')->user();
            /** @var Sold $lockedOrder */
            $lockedOrder = Sold::query()->lockForUpdate()->findOrFail($order->id);
            /** @var OrderFulfillment $fulfillment */
            $fulfillment = $lockedOrder->fulfillment()->lockForUpdate()->firstOrFail();

            $this->guardSwitch($lockedOrder, $fulfillment, $targetMode);

            $resolvedHub = $targetHub;
            if (in_array($targetMode, [FulfillmentMode::HUB_BASED, FulfillmentMode::POSTAL_ONLY_VIA_HUB], true) && !$resolvedHub) {
                $resolvedHub = $this->hubAssignmentService->assignForOrder((object) $this->buyerLocation($lockedOrder), $targetMode);
            }
            if (in_array($targetMode, [FulfillmentMode::HUB_BASED, FulfillmentMode::POSTAL_ONLY_VIA_HUB], true) && !$resolvedHub) {
                throw new \RuntimeException("Tanlangan mode uchun mos hub topilmadi.");
            }

            $this->cancelOpenTasks($fulfillment);

            $deliveryType = $targetMode === FulfillmentMode::DIRECT_COURIER
                ? 'delivery'
                : ($targetMode === FulfillmentMode::POSTAL_ONLY_VIA_HUB ? 'postal' : 'delivery');

            $lockedOrder->deliveryType = $deliveryType;
            $lockedOrder->save();

            $fulfillment->fulfillment_mode = $targetMode->value;
            $fulfillment->hub_id = $resolvedHub?->id;
            $fulfillment->first_mile_mode = $targetMode === FulfillmentMode::DIRECT_COURIER ? 'none' : 'courier_pickup';
            $fulfillment->last_mile_mode = match ($targetMode) {
                FulfillmentMode::DIRECT_COURIER => 'direct_delivery',
                FulfillmentMode::POSTAL_ONLY_VIA_HUB => 'postal_dispatch',
                default => 'courier_delivery',
            };
            $fulfillment->status_code = FulfillmentStatusCode::AWAITING_SELLER_PREP->value;
            $fulfillment->routing_snapshot = array_merge($fulfillment->routing_snapshot ?? [], [
                'admin_override' => true,
                'override_note' => $note,
                'hub_id' => $resolvedHub?->id,
                'hub_code' => $resolvedHub?->code,
                'hub_name' => $resolvedHub?->name,
            ]);
            $fulfillment->meta = array_merge($fulfillment->meta ?? [], [
                'last_mode_switch_at' => now()->toIso8601String(),
                'last_mode_switch_to' => $targetMode->value,
                'last_mode_switch_note' => $note,
                'mode_switch_log' => array_values([
                    ...collect(data_get($fulfillment->meta, 'mode_switch_log', []))->all(),
                    [
                        'at' => now()->toIso8601String(),
                        'target_mode' => $targetMode->value,
                        'hub_id' => $resolvedHub?->id,
                        'hub_name' => $resolvedHub?->name,
                        'note' => $note,
                        'admin_id' => $admin?->id,
                        'admin_name' => $admin?->name,
                    ],
                ]),
            ]);
            $fulfillment->save();

            CourierOrder::where('order_id', $lockedOrder->id)->update([
                'courier_id' => null,
                'status' => 0,
                'status_code' => 'pending',
                'updated_at' => now(),
            ]);

            $this->courierTaskOrchestratorService->ensureTasksForOrder($lockedOrder->fresh());

            return $fulfillment->fresh(['hub', 'courierTasks']);
        });
    }

    public function rerouteHub(Sold $order, Hub $targetHub, ?string $note = null): OrderFulfillment
    {
        return DB::transaction(function () use ($order, $targetHub, $note) {
            $admin = Auth::guard('admin')->user();
            /** @var Sold $lockedOrder */
            $lockedOrder = Sold::query()->lockForUpdate()->findOrFail($order->id);
            /** @var OrderFulfillment $fulfillment */
            $fulfillment = $lockedOrder->fulfillment()->lockForUpdate()->firstOrFail();

            $this->guardReroute($lockedOrder, $fulfillment);

            $fulfillment->hub_id = $targetHub->id;
            $fulfillment->routing_snapshot = array_merge($fulfillment->routing_snapshot ?? [], [
                'rerouted' => true,
                'reroute_note' => $note,
                'hub_id' => $targetHub->id,
                'hub_code' => $targetHub->code,
                'hub_name' => $targetHub->name,
            ]);
            $fulfillment->meta = array_merge($fulfillment->meta ?? [], [
                'last_reroute_at' => now()->toIso8601String(),
                'last_reroute_note' => $note,
                'hub_reroute_log' => array_values([
                    ...collect(data_get($fulfillment->meta, 'hub_reroute_log', []))->all(),
                    [
                        'at' => now()->toIso8601String(),
                        'hub_id' => $targetHub->id,
                        'hub_name' => $targetHub->name,
                        'note' => $note,
                        'admin_id' => $admin?->id,
                        'admin_name' => $admin?->name,
                    ],
                ]),
            ]);
            $fulfillment->save();

            CourierTask::query()
                ->where('fulfillment_id', $fulfillment->id)
                ->whereIn('status_code', [
                    CourierTaskStatusCode::PENDING->value,
                    CourierTaskStatusCode::ASSIGNED->value,
                    CourierTaskStatusCode::ACCEPTED->value,
                ])
                ->get()
                ->each(function (CourierTask $task) use ($targetHub) {
                    $task->hub_id = $targetHub->id;
                    if ($task->leg === CourierTaskLeg::FIRST_MILE->value) {
                        $task->dropoff_address = [
                            'hub_id' => $targetHub->id,
                            'name' => $targetHub->name,
                            'fullAddress' => $targetHub->address,
                            'lat' => $targetHub->lat,
                            'lon' => $targetHub->lon,
                            'city_name' => $targetHub->city_name,
                            'region_name' => $targetHub->region_name,
                            'country_code' => $targetHub->country_code,
                        ];
                    }
                    $task->save();
                });

            return $fulfillment->fresh(['hub', 'courierTasks']);
        });
    }

    private function guardSwitch(Sold $order, OrderFulfillment $fulfillment, FulfillmentMode $targetMode): void
    {
        if ($fulfillment->fulfillment_mode === $targetMode->value) {
            throw new \RuntimeException("Buyurtma allaqachon shu mode’da.");
        }

        if ((int) ($order->courier_id ?? 0) > 0 || $this->hasAssignedCourierTask($fulfillment)) {
            throw new \RuntimeException("Kuryer biriktirilgan buyurtmada mode almashtirib bo'lmaydi.");
        }

        if (in_array($fulfillment->status_code, [
            FulfillmentStatusCode::PACKED->value,
            FulfillmentStatusCode::LABELED->value,
            FulfillmentStatusCode::DISPATCHED_TO_POST->value,
            FulfillmentStatusCode::ASSIGNED_LAST_MILE->value,
            FulfillmentStatusCode::OUT_FOR_DELIVERY->value,
            FulfillmentStatusCode::DELIVERED->value,
            FulfillmentStatusCode::RETURNED->value,
            FulfillmentStatusCode::CANCELLED->value,
        ], true)) {
            throw new \RuntimeException("Bu bosqichdan keyin mode almashtirib bo'lmaydi.");
        }
    }

    private function guardReroute(Sold $order, OrderFulfillment $fulfillment): void
    {
        if ((int) ($order->courier_id ?? 0) > 0 || $this->hasAssignedCourierTask($fulfillment)) {
            throw new \RuntimeException("Kuryer biriktirilgan buyurtmada hubni almashtirib bo'lmaydi.");
        }

        if (!in_array($fulfillment->fulfillment_mode, [
            FulfillmentMode::HUB_BASED->value,
            FulfillmentMode::POSTAL_ONLY_VIA_HUB->value,
        ], true)) {
            throw new \RuntimeException("Faqat hub orqali yuradigan buyurtmada reroute qilish mumkin.");
        }

        if (!in_array($fulfillment->status_code, [
            FulfillmentStatusCode::AWAITING_SELLER_PREP->value,
            FulfillmentStatusCode::READY_FOR_PICKUP->value,
            FulfillmentStatusCode::PICKED_FROM_SELLER->value,
        ], true)) {
            throw new \RuntimeException("Bu bosqichda hub reroute qilib bo'lmaydi.");
        }
    }

    private function cancelOpenTasks(OrderFulfillment $fulfillment): void
    {
        CourierTask::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->whereIn('status_code', [
                CourierTaskStatusCode::PENDING->value,
                CourierTaskStatusCode::ASSIGNED->value,
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
            ])
            ->get()
            ->each(function (CourierTask $task) {
                if ($task->is_cod && $task->cod_reserved_at && !$task->cod_released_at) {
                    $this->codCapacityService->releaseReservation($task);
                }
                $task->status_code = CourierTaskStatusCode::CANCELLED->value;
                $task->save();
            });
    }

    private function hasAssignedCourierTask(OrderFulfillment $fulfillment): bool
    {
        return CourierTask::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->whereNotNull('courier_id')
            ->whereIn('status_code', [
                CourierTaskStatusCode::ASSIGNED->value,
                CourierTaskStatusCode::ACCEPTED->value,
                CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
            ])
            ->exists();
    }

    private function buyerLocation(Sold $order): array
    {
        $address = collect($order->address ?? [])->first() ?? [];

        return [
            'fullAddress' => $address['fullAddress'] ?? null,
            'lat' => $address['lat'] ?? null,
            'lon' => $address['lon'] ?? null,
            'country_code' => $address['country_code'] ?? 'UZ',
            'city_name' => $address['city_name'] ?? null,
            'region_name' => $address['region_name'] ?? null,
        ];
    }
}
