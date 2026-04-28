<?php

namespace App\Services;

use App\Events\OrderLifecycleUpdated;
use App\Models\CourierOrder;
use App\Models\SellerOrder;
use App\Models\Sold;

class OrderRealtimeService
{
    public function broadcastSoldCreated(Sold $sold, array $sellerIds, ?CourierOrder $courierOrder = null): void
    {
        $channels = [];
        foreach ($sellerIds as $sellerId) {
            $channels[] = "seller-store.{$sellerId}";
        }

        if ($sold->user_id) {
            $channels[] = "user.{$sold->user_id}";
        }

        if ($courierOrder && $courierOrder->status === 'pending' && !$courierOrder->courier_id) {
            $channels[] = 'courier.feed';
        }

        $this->dispatch(array_unique($channels), [
            'type' => 'order.created',
            'order_id' => (int) $sold->id,
            'user_id' => $sold->user_id ? (int) $sold->user_id : null,
            'sold_status' => (string) $sold->status,
            'seller_ids' => array_values(array_map('intval', $sellerIds)),
            'courier_order_id' => $courierOrder?->id ? (int) $courierOrder->id : null,
            'courier_status' => $courierOrder?->status,
            'refresh_hint' => 'orders',
            'at' => now()->toIso8601String(),
        ]);
    }

    public function broadcastSellerOrderUpdated(SellerOrder $sellerOrder, string $type): void
    {
        $sellerOrder->loadMissing('order');

        $channels = [
            "seller-store.{$sellerOrder->seller_id}",
        ];

        if ($sellerOrder->client_id) {
            $channels[] = "user.{$sellerOrder->client_id}";
        }

        if ($sellerOrder->courier_id) {
            $channels[] = "courier.{$sellerOrder->courier_id}";
        }

        $this->dispatch(array_unique($channels), [
            'type' => $type,
            'order_id' => $sellerOrder->order_id ? (int) $sellerOrder->order_id : null,
            'seller_order_id' => (int) $sellerOrder->id,
            'seller_id' => (int) $sellerOrder->seller_id,
            'courier_id' => $sellerOrder->courier_id ? (int) $sellerOrder->courier_id : null,
            'user_id' => $sellerOrder->client_id ? (int) $sellerOrder->client_id : null,
            'seller_status' => (int) $sellerOrder->status,
            'sold_status' => $sellerOrder->order?->status,
            'refresh_hint' => 'seller_orders',
            'at' => now()->toIso8601String(),
        ]);
    }

    public function broadcastCourierOrderUpdated(CourierOrder $courierOrder, string $type): void
    {
        $courierOrder->loadMissing(['order', 'items']);

        $channels = [];

        if ($courierOrder->courier_id) {
            $channels[] = "courier.{$courierOrder->courier_id}";
        } elseif ($courierOrder->status === 'pending') {
            $channels[] = 'courier.feed';
        }

        if ($courierOrder->user_id) {
            $channels[] = "user.{$courierOrder->user_id}";
        }

        foreach ($courierOrder->items->pluck('seller_id')->filter()->unique() as $sellerId) {
            $channels[] = "seller-store.{$sellerId}";
        }

        $this->dispatch(array_unique($channels), [
            'type' => $type,
            'order_id' => (int) $courierOrder->order_id,
            'courier_order_id' => (int) $courierOrder->id,
            'courier_id' => $courierOrder->courier_id ? (int) $courierOrder->courier_id : null,
            'user_id' => $courierOrder->user_id ? (int) $courierOrder->user_id : null,
            'courier_status' => (string) $courierOrder->status,
            'sold_status' => $courierOrder->order?->status,
            'seller_ids' => $courierOrder->items->pluck('seller_id')->filter()->unique()->values()->map(fn ($id) => (int) $id)->all(),
            'refresh_hint' => 'courier_orders',
            'at' => now()->toIso8601String(),
        ]);
    }

    private function dispatch(array $channels, array $payload): void
    {
        if (empty($channels)) {
            return;
        }

        broadcast(new OrderLifecycleUpdated($channels, $payload));
    }
}
