<?php

namespace App\Observers;

use App\Models\CourierOrder;
use App\Services\CourierBroadcaster;
use App\Services\OrderRealtimeService;
use Illuminate\Support\Facades\DB;

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
            // Push faqat tranzaksiya commit bo'lgach ketadi — rollback bo'lsa
            // mavjud bo'lmagan buyurtma uchun push yubormaymiz.
            $orderId = (int) $order->order_id;
            DB::afterCommit(fn () => $this->broadcaster->notifyPendingForOrder($orderId));
            $this->realtimeService->broadcastCourierOrderUpdated($order, 'courier_order.available');
        }
    }

    public function updated(CourierOrder $order): void
    {
        // Status 'pending' ga endi o'tdi (oldin boshqa edi).
        // Eslatma: query-builder mass update bu observer'ni ISHGA TUSHIRMAYDI —
        // shu sabab to'lov/seller o'tishlarida push CourierBroadcaster orqali
        // xizmat qatlamidan aniq chaqiriladi. Bu faqat model-instance
        // update'lari uchun qo'shimcha himoya.
        if (
            $order->wasChanged('status')
            && $order->status === 'pending'
            && $order->getOriginal('status') !== 'pending'
            && $this->isAvailableForAllCouriers($order)
        ) {
            $orderId = (int) $order->order_id;
            DB::afterCommit(fn () => $this->broadcaster->notifyPendingForOrder($orderId));
            $this->realtimeService->broadcastCourierOrderUpdated($order, 'courier_order.available');
        }
    }

    private function isAvailableForAllCouriers(CourierOrder $order): bool
    {
        return $order->status === 'pending' && empty($order->courier_id);
    }
}
