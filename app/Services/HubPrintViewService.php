<?php

namespace App\Services;

use App\Models\OrderFulfillment;
use Illuminate\Support\Arr;

class HubPrintViewService
{
    public function labelData(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $address = collect($order?->address ?? [])->first() ?? [];
        $customerName = trim((string) (
            Arr::get($address, 'fullName')
            ?? $order?->recipient_name
            ?? $order?->user?->full_name
            ?? 'Mijoz'
        ));
        $phone = trim((string) (
            Arr::get($address, 'phoneNumber')
            ?? $order?->recipient_phone
            ?? $order?->user?->phone_number
            ?? '—'
        ));
        $fullAddress = trim((string) (
            Arr::get($address, 'fullAddress')
            ?? Arr::get($address, 'branch_address')
            ?? $order?->recipient_address
            ?? 'Manzil kiritilmagan'
        ));

        return [
            'order_number' => '#ORD-' . $fulfillment->order_id,
            'hub_name' => $fulfillment->hub?->name,
            'label_code' => $fulfillment->label_code ?: 'LBL-' . $fulfillment->id,
            'tracking' => $fulfillment->postal_tracking_number ?: null,
            'customer_name' => $customerName,
            'customer_phone' => $phone,
            'address' => $fullAddress,
            'payment_method' => $fulfillment->is_cod ? 'Naqd (COD)' : 'Oldindan to‘langan',
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items_count' => (int) collect($order?->items ?? [])->sum(fn ($item) => (int) ($item['count_item'] ?? $item['count'] ?? 1)),
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'delivery_type' => (string) ($order?->deliveryType ?? 'delivery'),
        ];
    }

    public function receiptData(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $items = collect($order?->items ?? [])->map(function ($item) {
            $qty = (int) ($item['count_item'] ?? $item['count'] ?? 1);
            $price = (float) ($item['item_price'] ?? $item['price'] ?? 0);

            return [
                'title' => (string) ($item['name'] ?? $item['title'] ?? 'Mahsulot'),
                'qty' => $qty,
                'price' => $price,
                'total' => $qty * $price,
            ];
        })->values()->all();

        return [
            'order_number' => '#ORD-' . $fulfillment->order_id,
            'hub_name' => $fulfillment->hub?->name,
            'customer_name' => $order?->user?->full_name ?: 'Mijoz',
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'payment_method' => $fulfillment->is_cod ? 'Naqd (COD)' : 'Oldindan to‘langan',
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items' => $items,
            'items_count' => (int) collect($items)->sum('qty'),
            'subtotal' => (int) round((float) collect($items)->sum('total')),
            'delivery_amount' => (int) round((float) ($order?->deliveryPrice ?? 0)),
            'discount_amount' => (int) round((float) ($order?->discountAmount ?? 0)),
            'total_amount' => (int) round((float) ($order?->amount ?? 0)),
        ];
    }
}
