<?php

namespace App\Console\Commands;

use App\Enums\CourierOrderStatusCode;
use App\Enums\CourierTaskStatusCode;
use App\Enums\OrderStatusCode;
use App\Models\Sold;
use App\Services\AdminOrderStatusSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Asosiy buyurtma yakunlangan (yetkazildi / mijoz oldi / qaytdi / bekor),
 * lekin kuryer qatori yoki topshirig'i ochiq qolib ketgan buyurtmalarni
 * topib, kuryer tomonini asosiy holatga keltiradi. Kuryersiz to'g'ridan-
 * to'g'ri "yo'lda" buyurtmalar ham umumiy ro'yxatdan chiqariladi.
 */
class ReconcileCourierOrders extends Command
{
    protected $signature = 'orders:reconcile-courier {--dry-run : Faqat ko‘rsatish, o‘zgartirmaslik} {--limit=2000}';

    protected $description = 'Asosiy buyurtma bilan kuryer buyurtmalari/topshiriqlari holatini moslaydi';

    public function handle(AdminOrderStatusSyncService $sync): int
    {
        $closedMain = [
            OrderStatusCode::DELIVERED->value,
            OrderStatusCode::CUSTOMER_RECEIVED->value,
            OrderStatusCode::RETURNED->value,
            OrderStatusCode::CANCELLED->value,
        ];
        $openCourier = [
            CourierOrderStatusCode::PAYMENT_PENDING->value,
            CourierOrderStatusCode::PENDING->value,
            CourierOrderStatusCode::IN_DELIVERY->value,
        ];
        $openTask = [
            CourierTaskStatusCode::ASSIGNED->value,
            CourierTaskStatusCode::ACCEPTED->value,
            CourierTaskStatusCode::ARRIVED_AT_PICKUP->value,
            CourierTaskStatusCode::PICKED_UP->value,
            CourierTaskStatusCode::DROPPED_OFF->value,
        ];

        $staleRows = DB::table('courier_orders as co')
            ->join('solds as s', 's.id', '=', 'co.order_id')
            ->whereIn('s.status_code', $closedMain)
            ->where(fn ($q) => $q->whereIn('co.status_code', $openCourier)->orWhereNull('co.status_code'))
            ->pluck('s.id');
        $staleTasks = DB::table('courier_tasks as t')
            ->join('solds as s', 's.id', '=', 't.order_id')
            ->whereIn('s.status_code', $closedMain)
            ->whereIn('t.status_code', $openTask)
            ->pluck('s.id');
        // Kuryersiz to'g'ridan-to'g'ri yetkazish "yo'lda", lekin umumiy ro'yxatda kutib turibdi
        $directInDelivery = DB::table('courier_orders as co')
            ->join('solds as s', 's.id', '=', 'co.order_id')
            ->join('order_fulfillments as f', 'f.order_id', '=', 's.id')
            ->where('s.status_code', OrderStatusCode::IN_DELIVERY->value)
            ->where('f.fulfillment_mode', 'direct_courier')
            ->whereNull('co.courier_id')
            ->where(fn ($q) => $q->whereNull('s.courier_id')->orWhere('s.courier_id', 0))
            ->whereIn('co.status_code', [CourierOrderStatusCode::PENDING->value, CourierOrderStatusCode::PAYMENT_PENDING->value])
            ->pluck('s.id');

        $ids = $staleRows->merge($staleTasks)->merge($directInDelivery)->unique()->take((int) $this->option('limit'))->values();
        $this->info("Topildi: {$ids->count()} ta buyurtma (kuryer qatori: {$staleRows->unique()->count()}, topshiriq: {$staleTasks->unique()->count()}, kuryersiz yo‘lda: {$directInDelivery->unique()->count()})");

        if ($this->option('dry-run') || $ids->isEmpty()) {
            if ($ids->isNotEmpty()) {
                $this->line('ID: '.$ids->take(50)->implode(', ').($ids->count() > 50 ? ' …' : ''));
            }

            return self::SUCCESS;
        }

        $fixed = 0;
        foreach ($ids as $id) {
            $order = Sold::query()->find($id);
            if (! $order) {
                continue;
            }
            try {
                $sync->reconcileCourierSide($order);
                $fixed++;
            } catch (\Throwable $e) {
                $this->warn("#{$id}: ".$e->getMessage());
                report($e);
            }
        }
        $this->info("Tuzatildi: {$fixed} ta");

        return self::SUCCESS;
    }
}
