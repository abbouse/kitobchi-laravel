<?php

namespace App\Services;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskLeg;
use App\Enums\CourierTaskStatusCode;
use App\Enums\OrderStatusCode;
use App\Models\CourierOrder;
use App\Models\CourierTask;
use App\Models\Couriers;
use App\Models\Sold;
use App\Support\CourierLimits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kuryerni buyurtmaga biriktirish va bo'shatish (kuryer ilovasi va boshqaruv
 * uchun umumiy qoida).
 */
class CourierAssignmentService
{
    public function __construct(
        private readonly CourierTaskOrchestratorService $orchestrator,
        private readonly OrderRealtimeService $realtime,
    ) {}

    /**
     * Qabul qilingan topshiriqlardan keyin buyurtma va kuryer qatorini
     * yangilaydi. Last-mile'da Sold holati o'zgartirilmaydi (avval PACKING'ga
     * tushirilib, mijozga yetkazish QR'i ishlamay qolardi).
     *
     * @param  Collection<int, CourierTask>  $acceptedTasks
     */
    public function attach(Sold $sold, CourierOrder $courierOrder, Couriers $courier, Collection $acceptedTasks): void
    {
        $isLastMile = $acceptedTasks->contains(fn (CourierTask $t) => $t->leg === CourierTaskLeg::LAST_MILE->value);

        $sold->courier_id = $courier->id;
        $sold->courierName = trim($courier->first_name.' '.$courier->last_name);
        $current = OrderStatusCode::tryFrom((string) $sold->status_code);
        if (! $isLastMile && ($current === null || $current === OrderStatusCode::PENDING)) {
            $sold->status = OrderStatusCode::PACKING->legacy();
            $sold->status_code = OrderStatusCode::PACKING->value;
        }
        $sold->save();

        $courierOrder->courier_id = $courier->id;
        $courierOrder->status = CourierOrderStatusCode::IN_DELIVERY->legacy();
        $courierOrder->status_code = CourierOrderStatusCode::IN_DELIVERY->value;
        $courierOrder->courierPrice = (int) $acceptedTasks->sum(
            fn (CourierTask $task) => max(0, (int) $task->fee_amount - (int) $task->bonus_amount)
        );
        $courierOrder->courierBonus = (int) $acceptedTasks->sum('bonus_amount');
        $courierOrder->save();
    }

    /**
     * Boshqaruvdan kuryerni to'g'ridan-to'g'ri biriktirish (qayta biriktirish).
     *
     * @throws \RuntimeException
     */
    public function assign(Sold $sold, Couriers $courier, bool $enforceLimit = true): CourierOrder
    {
        if (! $courier->canWork()) {
            throw new \RuntimeException('Kuryer faol emas yoki bloklangan.');
        }

        $courierOrder = DB::transaction(function () use ($sold, $courier, $enforceLimit) {
            Couriers::query()->whereKey($courier->id)->lockForUpdate()->first();
            if ($enforceLimit && CourierLimits::activeOrdersCount((int) $courier->id) >= CourierLimits::maxActiveOrders()) {
                throw new \RuntimeException('Kuryerda faol buyurtmalar limiti to\'lgan.');
            }

            $sold = Sold::query()->lockForUpdate()->findOrFail($sold->id);
            $courierOrder = CourierOrder::query()
                ->where('order_id', $sold->id)
                ->whereNull('courier_id')
                ->whereIn('status_code', [CourierOrderStatusCode::PENDING->value, CourierOrderStatusCode::PAYMENT_PENDING->value])
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if (! $courierOrder) {
                throw new \RuntimeException("Bu buyurtmada bo'sh kuryer o'rni yo'q (avval joriy kuryerni bo'shating).");
            }

            $accepted = $this->orchestrator->acceptAvailableTasksForCourier($sold, $courier);
            if ($accepted->isEmpty()) {
                throw new \RuntimeException("Biriktiriladigan ochiq topshiriq yo'q.");
            }

            $this->attach($sold, $courierOrder, $courier, $accepted);

            return $courierOrder;
        });

        $this->realtime->broadcastCourierOrderUpdated($courierOrder->fresh(), 'courier_order.confirmed');

        return $courierOrder;
    }

    /**
     * Kuryerni buyurtmadan bo'shatadi: buyurtma yana umumiy ro'yxatga chiqadi.
     *
     * @param  bool  $allowPickedUp  faqat boshqaruv uchun (kitob kuryerda bo'lsa ham)
     *
     * @throws \RuntimeException
     */
    public function release(Sold $sold, int $courierId, string $reason, array $actor = [], bool $allowPickedUp = false): CourierOrder
    {
        $courierOrder = DB::transaction(function () use ($sold, $courierId, $reason, $actor, $allowPickedUp) {
            $sold = Sold::query()->lockForUpdate()->findOrFail($sold->id);
            $courierOrder = CourierOrder::query()
                ->where('order_id', $sold->id)
                ->where('courier_id', $courierId)
                ->where('status_code', CourierOrderStatusCode::IN_DELIVERY->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();
            if (! $courierOrder) {
                throw new \RuntimeException('Kuryerda bu buyurtma faol emas.');
            }

            $released = $this->orchestrator->releaseCourierTasks($sold, $courierId, $allowPickedUp, [
                'reason' => $reason,
            ] + $actor);

            if ($released->isEmpty() && ! $allowPickedUp) {
                throw new \RuntimeException("Bo'shatiladigan topshiriq topilmadi.");
            }

            $courierOrder->courier_id = null;
            $courierOrder->status = CourierOrderStatusCode::PENDING->legacy();
            $courierOrder->status_code = CourierOrderStatusCode::PENDING->value;
            $courierOrder->save();

            if ((int) $sold->courier_id === $courierId) {
                $sold->courier_id = null;
                $sold->courierName = null;
                $sold->save();
            }

            Log::info('Courier released from order', [
                'order_id' => $sold->id,
                'courier_id' => $courierId,
                'reason' => $reason,
                'actor' => $actor,
                'tasks' => $released->pluck('id')->all(),
            ]);

            return $courierOrder;
        });

        $this->realtime->broadcastCourierOrderUpdated($courierOrder->fresh(), 'courier_order.available');
        // "Yangi buyurtma" push bir marta yuboriladi (6 soatlik to'siq) —
        // bo'shatilgan buyurtma uchun qayta yuborishga ruxsat beramiz
        \Illuminate\Support\Facades\Cache::forget('courier-new-order-push:'.$courierOrder->id);
        app(CourierBroadcaster::class)->notifyPendingForOrder((int) $sold->id);

        return $courierOrder;
    }

    /** Kuryer hali mahsulotni olib ketmaganmi (o'zi bo'shata oladimi). */
    public function canCourierSelfRelease(Sold $sold, int $courierId): bool
    {
        return ! CourierTask::query()
            ->where('order_id', $sold->id)
            ->where('courier_id', $courierId)
            ->whereIn('status_code', [
                CourierTaskStatusCode::PICKED_UP->value,
                CourierTaskStatusCode::DROPPED_OFF->value,
            ])
            ->exists();
    }
}
