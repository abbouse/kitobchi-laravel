<?php

namespace App\Services;

use App\Models\OrderFulfillment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
            'customer_phone' => $this->formatPhoneForLabel($phone),
            'address' => $fullAddress,
            'payment_method' => $fulfillment->is_cod ? 'Naqd olinadi' : 'Karta orqali to‘langan',
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items_count' => (int) collect($order?->items ?? [])->sum(fn ($item) => (int) ($item['count_item'] ?? $item['count'] ?? 1)),
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'created_at_pretty' => $this->formatPrettyDateTime($order?->created_at),
            'delivery_type' => (string) ($order?->deliveryType ?? 'delivery'),
            'delivery_type_label' => $this->resolveDeliveryTypeLabel((string) ($order?->deliveryType ?? 'delivery')),
            'total_amount' => (int) round((float) ($order?->amount ?? 0)),
            'meta_hub_name' => $fulfillment->hub?->name ?: 'Hub aniqlanmagan',
            'delight_message' => $this->resolveReceiptDelightMessage($fulfillment),
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

    private function formatPhoneForLabel(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '—';
        }

        if (Str::startsWith($digits, '998') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        if (strlen($digits) === 9) {
            return '+998' . $digits;
        }

        return Str::startsWith($digits, '+') ? $digits : '+' . $digits;
    }

    private function formatPrettyDateTime(CarbonInterface|string|null $value): string
    {
        if (! $value) {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
        $months = [
            1 => 'yanvar',
            2 => 'fevral',
            3 => 'mart',
            4 => 'aprel',
            5 => 'may',
            6 => 'iyun',
            7 => 'iyul',
            8 => 'avgust',
            9 => 'sentyabr',
            10 => 'oktyabr',
            11 => 'noyabr',
            12 => 'dekabr',
        ];

        return $date->day . '-' . ($months[$date->month] ?? $date->format('m')) . ' ' . $date->format('H:i');
    }

    private function resolveReceiptDelightMessage(OrderFulfillment $fulfillment): string
    {
        $variants = [
            'Kitoblar yo‘lda, choyni damlab qo‘ying.',
            'Xaridingiz uchun rahmat, bu safar ham javon quvonadi.',
            'Bugun bir kitob, ertaga yangi dunyo.',
            'Kitobxon yurak uchun kichik bayram jo‘natdik.',
            'Sahifalar yaqin, kayfiyat baland bo‘lsin.',
            'Yana bitta yaxshi hikoya yo‘lga chiqdi.',
        ];

        $index = ((int) $fulfillment->order_id) % count($variants);

        return $variants[$index];
    }

    private function resolveDeliveryTypeLabel(string $deliveryType): string
    {
        return match (strtolower(trim($deliveryType))) {
            'postal', 'mail_service', 'uzpost', 'pochta' => 'Pochta orqali',
            'pickup', 'instore', 'in_store', 'store_pickup' => 'O‘zi olib ketish',
            default => 'Kuryer orqali',
        };
    }
}
