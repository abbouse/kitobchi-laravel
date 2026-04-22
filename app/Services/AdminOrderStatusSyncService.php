<?php

namespace App\Services;

use App\Models\CourierOrder;
use App\Models\SellerOrder;
use App\Models\Sold;
use Illuminate\Support\Facades\DB;

class AdminOrderStatusSyncService
{
    public const SELLER_STATUSES = [
        0 => ['label' => "To'lov jarayonida", 'badge' => 'badge-muted'],
        1 => ['label' => 'Yangi buyurtma', 'badge' => 'badge-info'],
        2 => ['label' => 'Kuryerga berildi', 'badge' => 'badge-warning'],
        3 => ['label' => "Kuryerga berildi (legacy)", 'badge' => 'badge-warning'],
        4 => ['label' => 'Bekor qilindi', 'badge' => 'badge-danger'],
    ];

    public const COURIER_STATUSES = [
        'pay_process' => ['label' => "To'lov jarayonida", 'badge' => 'badge-muted'],
        'pending' => ['label' => 'Kutilmoqda', 'badge' => 'badge-info'],
        'in_delivery' => ['label' => "Yo'lda", 'badge' => 'badge-warning'],
        'delivered' => ['label' => 'Yetkazildi', 'badge' => 'badge-success'],
        'rejected' => ['label' => 'Bekor qilindi', 'badge' => 'badge-danger'],
    ];

    public function __construct(private readonly OrderService $orderService) {}

    public function updateMainOrder(Sold $order, string $status): void
    {
        DB::transaction(function () use ($order, $status) {
            if ($status === 'F') {
                $this->orderService->cancelOrder($order, strict: false);
                return;
            }

            if ($status === 'C' && (int) $order->paymentStatus !== 2) {
                $order->paymentStatus = 2;
            }

            $order->status = $status;
            $order->save();

            SellerOrder::where('order_id', $order->id)->update([
                'status' => $this->mapMainToSeller($status, $order->paymentStatus),
                'updated_at' => now(),
            ]);

            CourierOrder::where('order_id', $order->id)->update([
                'status' => $this->mapMainToCourier($status, $order->paymentStatus),
                'updated_at' => now(),
            ]);
        });
    }

    public function updateSellerOrder(SellerOrder $sellerOrder, int $status): void
    {
        DB::transaction(function () use ($sellerOrder, $status) {
            $sellerOrder->update(['status' => $status]);

            $order = $sellerOrder->order()->first();
            if (!$order) {
                return;
            }

            if ($status === 4) {
                $this->orderService->cancelOrder($order, strict: false);
                return;
            }

            $order->status = match ($status) {
                2, 3 => 'B',
                default => 'A',
            };
            $order->save();

            CourierOrder::where('order_id', $order->id)->update([
                'status' => $this->mapSellerToCourier($status, $order->paymentStatus),
                'updated_at' => now(),
            ]);
        });
    }

    public function updateCourierOrder(CourierOrder $courierOrder, string $status): void
    {
        DB::transaction(function () use ($courierOrder, $status) {
            $courierOrder->update(['status' => $status]);

            $order = $courierOrder->order()->first();
            if (!$order) {
                return;
            }

            if ($status === 'rejected') {
                $this->orderService->cancelOrder($order, strict: false);
                return;
            }

            $order->status = match ($status) {
                'delivered' => 'C',
                'in_delivery' => 'B',
                default => 'A',
            };

            if ($status === 'delivered' && (int) $order->paymentStatus !== 2) {
                $order->paymentStatus = 2;
            }

            $order->save();

            SellerOrder::where('order_id', $order->id)->update([
                'status' => $this->mapCourierToSeller($status),
                'updated_at' => now(),
            ]);
        });
    }

    public function mapMainToSeller(string $status, int|string|null $paymentStatus = null): int
    {
        if ((int) $paymentStatus === 1 && in_array($status, ['A', 'P'], true)) {
            return 0;
        }

        return match ($status) {
            'B', 'C' => 2,
            'F' => 4,
            default => 1,
        };
    }

    public function mapMainToCourier(string $status, int|string|null $paymentStatus = null): string
    {
        if ((int) $paymentStatus === 1 && in_array($status, ['A', 'P'], true)) {
            return 'pay_process';
        }

        return match ($status) {
            'B' => 'in_delivery',
            'C' => 'delivered',
            'F' => 'rejected',
            default => 'pending',
        };
    }

    public function mapSellerToCourier(int $status, int|string|null $paymentStatus = null): string
    {
        return match ($status) {
            2, 3 => 'in_delivery',
            4 => 'rejected',
            default => ((int) $paymentStatus === 1 ? 'pay_process' : 'pending'),
        };
    }

    public function mapCourierToSeller(string $status): int
    {
        return match ($status) {
            'delivered', 'in_delivery' => 2,
            'rejected' => 4,
            default => 1,
        };
    }
}
