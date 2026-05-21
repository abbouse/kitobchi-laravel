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
        $locale = $this->resolveLocale($order?->user?->locale);
        $address = collect($order?->address ?? [])->first() ?? [];
        $customerName = trim((string) (
            Arr::get($address, 'fullName')
            ?? $order?->recipient_name
            ?? $order?->user?->full_name
            ?? $this->text($locale, 'customer_fallback')
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
            'locale' => $locale,
            'hub_name' => $fulfillment->hub?->name,
            'label_code' => $fulfillment->label_code ?: 'LBL-' . $fulfillment->id,
            'tracking' => $fulfillment->postal_tracking_number ?: null,
            'customer_name' => $customerName,
            'customer_phone' => $this->formatPhoneForLabel($phone),
            'address' => $fullAddress,
            'payment_method' => $fulfillment->is_cod
                ? $this->text($locale, 'cash_collect')
                : $this->text($locale, 'card_paid'),
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items_count' => (int) collect($order?->items ?? [])->sum(fn ($item) => (int) ($item['count_item'] ?? $item['count'] ?? 1)),
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'created_at_pretty' => $this->formatPrettyDateTime($order?->created_at, $locale),
            'delivery_type' => (string) ($order?->deliveryType ?? 'delivery'),
            'delivery_type_label' => $this->resolveDeliveryTypeLabel((string) ($order?->deliveryType ?? 'delivery'), $locale),
            'total_amount' => (int) round((float) ($order?->amount ?? 0)),
            'meta_hub_name' => $fulfillment->hub?->name ?: $this->text($locale, 'hub_unknown'),
            'delight_message' => $this->resolveReceiptDelightMessage($fulfillment, $locale),
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
            return $this->formatUzbekPhone($digits);
        }

        if (strlen($digits) === 9) {
            return $this->formatUzbekPhone('998' . $digits);
        }

        return Str::startsWith($digits, '+') ? $digits : '+' . $digits;
    }

    private function formatUzbekPhone(string $digits): string
    {
        return sprintf(
            '+%s %s %s %s %s',
            substr($digits, 0, 3),
            substr($digits, 3, 2),
            substr($digits, 5, 3),
            substr($digits, 8, 2),
            substr($digits, 10, 2),
        );
    }

    private function formatPrettyDateTime(CarbonInterface|string|null $value, string $locale): string
    {
        if (! $value) {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
        $month = $this->monthLabel($date->month, $locale);

        return match ($locale) {
            'ja' => $date->month . '月' . $date->day . '日 ' . $date->format('H:i'),
            default => $date->day . '-' . $month . ' ' . $date->format('H:i'),
        };
    }

    private function resolveReceiptDelightMessage(OrderFulfillment $fulfillment, string $locale): string
    {
        $variants = match ($locale) {
            'ru' => [
                'Книги уже в пути, можно заваривать чай.',
                'Спасибо за покупку, полка сегодня станет счастливее.',
                'Одна хорошая книга уже направляется к вам.',
                'Страницы близко, настроение пусть будет ещё ближе.',
                'Новая история уже выбрала вас.',
                'Чуть-чуть терпения и будет отличный вечер чтения.',
            ],
            'en' => [
                'Your next good read is already on the way.',
                'Thanks for your order, your shelf is getting happier.',
                'A better evening is traveling to you in pages.',
                'Tea can wait, the book is already coming.',
                'A fresh story is heading your way.',
                'Reading mood: almost delivered.',
            ],
            'ja' => [
                '新しい物語が、もうすぐあなたのもとへ届きます。',
                'ご注文ありがとうございます。本棚が少しうれしくなります。',
                '次の読書時間が、もう向かっています。',
                'あと少しで、ページの旅が始まります。',
                '今日は本にやさしい一日になりそうです。',
                '読書の楽しみ、ただいまお届け中です。',
            ],
            default => [
                'Kitoblar yo‘lda, choyni damlab qo‘ying.',
                'Xaridingiz uchun rahmat, bu safar ham javon quvonadi.',
                'Bugun bir kitob, ertaga yangi dunyo.',
                'Kitobxon yurak uchun kichik bayram jo‘natdik.',
                'Sahifalar yaqin, kayfiyat baland bo‘lsin.',
                'Yana bitta yaxshi hikoya yo‘lga chiqdi.',
            ],
        };

        $index = ((int) $fulfillment->order_id) % count($variants);

        return $variants[$index];
    }

    private function resolveDeliveryTypeLabel(string $deliveryType, string $locale): string
    {
        $normalized = strtolower(trim($deliveryType));

        return match ($normalized) {
            'postal', 'mail_service', 'uzpost', 'pochta' => $this->text($locale, 'delivery_postal'),
            'pickup', 'instore', 'in_store', 'store_pickup' => $this->text($locale, 'delivery_pickup'),
            default => $this->text($locale, 'delivery_courier'),
        };
    }

    private function resolveLocale(?string $locale): string
    {
        $normalized = strtolower(trim((string) $locale));

        return in_array($normalized, ['uz', 'ru', 'en', 'ja'], true) ? $normalized : 'uz';
    }

    private function monthLabel(int $month, string $locale): string
    {
        $months = [
            'uz' => [1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel', 5 => 'may', 6 => 'iyun', 7 => 'iyul', 8 => 'avgust', 9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr'],
            'ru' => [1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря'],
            'en' => [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'],
            'ja' => [1 => '1月', 2 => '2月', 3 => '3月', 4 => '4月', 5 => '5月', 6 => '6月', 7 => '7月', 8 => '8月', 9 => '9月', 10 => '10月', 11 => '11月', 12 => '12月'],
        ];

        return $months[$locale][$month] ?? (string) $month;
    }

    private function text(string $locale, string $key): string
    {
        $map = [
            'uz' => [
                'customer_fallback' => 'Mijoz',
                'card_paid' => 'Karta orqali to‘langan',
                'cash_collect' => 'Naqd olinadi',
                'delivery_postal' => 'Pochta orqali',
                'delivery_pickup' => 'O‘zi olib ketish',
                'delivery_courier' => 'Kuryer orqali',
                'hub_unknown' => 'Hub aniqlanmagan',
            ],
            'ru' => [
                'customer_fallback' => 'Клиент',
                'card_paid' => 'Оплачено картой',
                'cash_collect' => 'Наличные к получению',
                'delivery_postal' => 'Через почту',
                'delivery_pickup' => 'Самовывоз',
                'delivery_courier' => 'Через курьера',
                'hub_unknown' => 'Хаб не указан',
            ],
            'en' => [
                'customer_fallback' => 'Customer',
                'card_paid' => 'Paid by card',
                'cash_collect' => 'Cash to collect',
                'delivery_postal' => 'Via post',
                'delivery_pickup' => 'Self pickup',
                'delivery_courier' => 'Via courier',
                'hub_unknown' => 'Hub not set',
            ],
            'ja' => [
                'customer_fallback' => 'お客様',
                'card_paid' => 'カードで支払い済み',
                'cash_collect' => '現金回収あり',
                'delivery_postal' => '郵送でお届け',
                'delivery_pickup' => '店頭受け取り',
                'delivery_courier' => '配達員がお届け',
                'hub_unknown' => 'ハブ未設定',
            ],
        ];

        return $map[$locale][$key] ?? $map['uz'][$key] ?? $key;
    }
}
