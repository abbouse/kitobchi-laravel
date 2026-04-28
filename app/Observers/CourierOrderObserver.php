<?php

namespace App\Observers;

use App\Models\CourierOrder;
use App\Services\CourierBroadcaster;
use App\Services\OrderRealtimeService;

/**
 * CourierOrder uchun observer.
 * Vazifasi: status `pending` ga o'tganda barcha kuryerlarga FCM yuborish.
 *
 * Bu observer ikki holatni qo'llaydi:
 *  - `created`  — yangi yaratilgan order, agar status='pending' bo'lsa darhol bildirish.
 *  - `updated`  — eski statusdan 'pending' ga o'tish (masalan, to'lov tasdiqlangandan keyin).
 *
 * `to'g'ridan-to'g'ri kuryerga` (courier_id != null) order yaratilgan bo'lsa, bildirish kerak emas.
 */
class CourierOrderObserver
{
    public function __construct(
        private readonly CourierBroadcaster $broadcaster,
        private readonly OrderRealtimeService $realtimeService,
    ) {
    }

    public function created(CourierOrder $order): void
    {
        if ($this->isAvailableForAllCouriers($order)) {
            $this->broadcaster->notifyNewOrderAvailable($order);
            $this->realtimeService->broadcastCourierOrderUpdated($order, 'courier_order.available');
        }
    }

    public function updated(CourierOrder $order): void
    {
        // Status 'pending' ga endi o'tdi (oldin boshqa edi)
        if (
            $order->wasChanged('status')
            && $order->status === 'pending'
            && $order->getOriginal('status') !== 'pending'
            && $this->isAvailableForAllCouriers($order)
        ) {
            $this->broadcaster->notifyNewOrderAvailable($order);
            $this->realtimeService->broadcastCourierOrderUpdated($order, 'courier_order.available');
        }
    }

    private function isAvailableForAllCouriers(CourierOrder $order): bool
    {
        return $order->status === 'pending' && empty($order->courier_id);
    }
}
