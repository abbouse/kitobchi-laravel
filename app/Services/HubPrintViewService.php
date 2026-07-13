<?php

namespace App\Services;

use App\Models\OrderFulfillment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class HubPrintViewService
{
    public function labelData(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $locale = $this->resolveLocale($order?->user?->locale);
        $address = collect($order?->address ?? [])->first() ?? [];
        $items = $this->mapPrintableItems($order?->items ?? [], $locale);
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
        $scanCode = trim((string) $fulfillment->label_code);
        if ($scanCode === '') {
            $scanCode = 'ORD-'.$fulfillment->order_id;
        }

        return [
            'order_number' => '#ORD-'.$fulfillment->order_id,
            'locale' => $locale,
            'hub_name' => $fulfillment->hub?->name,
            'label_code' => $scanCode,
            'qr_data_uri' => $this->makeQrDataUri($scanCode),
            'tracking' => $fulfillment->postal_tracking_number ?: null,
            'customer_name' => $customerName,
            'customer_phone' => $this->formatPhoneForLabel($phone),
            'address' => $fullAddress,
            'payment_method' => $fulfillment->is_cod
                ? $this->text($locale, 'cash_collect')
                : $this->text($locale, 'card_paid'),
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items_count' => (int) collect($items)
                ->reject(fn (array $item) => $item['is_cancelled'])
                ->sum('qty'),
            'items_preview' => collect($items)->take(3)->values()->all(),
            'items_preview_hidden_count' => max(0, count($items) - 3),
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'created_at_pretty' => $this->formatPrettyDateTime($order?->created_at, $locale),
            'delivery_type' => (string) ($order?->deliveryType ?? 'delivery'),
            'delivery_type_label' => $this->resolveDeliveryTypeLabel((string) ($order?->deliveryType ?? 'delivery'), $locale),
            'total_amount' => (int) round((float) ($order?->amount ?? 0)),
            'meta_hub_name' => $fulfillment->hub?->name ?: $this->text($locale, 'hub_unknown'),
            'delight_message' => $this->resolveReceiptDelightMessage($fulfillment, $locale),
            'cancel_state_label' => $this->text($locale, 'cancelled_item_label'),
            'more_items_label' => $this->text($locale, 'more_items'),
        ];
    }

    private function makeQrDataUri(string $data): string
    {
        return (new Builder(
            writer: new SvgWriter,
            writerOptions: [
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
                SvgWriter::WRITER_OPTION_COMPACT => true,
            ],
            data: $data,
            // ASCII label kodlari uchun scannerlarda eng keng mos encoding.
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::None,
        ))->build()->getDataUri();
    }

    public function receiptData(OrderFulfillment $fulfillment): array
    {
        $order = $fulfillment->order;
        $locale = $this->resolveLocale($order?->user?->locale);
        $items = $this->mapPrintableItems($order?->items ?? [], $locale);
        $activeItems = collect($items)->reject(fn (array $item) => $item['is_cancelled'])->values();

        return [
            'order_number' => '#ORD-'.$fulfillment->order_id,
            'hub_name' => $fulfillment->hub?->name,
            'customer_name' => $order?->user?->full_name ?: 'Mijoz',
            'created_at' => optional($order?->created_at)?->format('d.m.Y H:i'),
            'payment_method' => $fulfillment->is_cod ? 'Naqd (COD)' : 'Oldindan to‘langan',
            'cod_amount' => (int) ($fulfillment->cash_collect_amount ?? 0),
            'items' => $items,
            'items_count' => (int) $activeItems->sum('qty'),
            'subtotal' => (int) round((float) $activeItems->sum('total')),
            'delivery_amount' => (int) round((float) ($order?->deliveryPrice ?? 0)),
            'discount_amount' => (int) round((float) (($order?->discountAmount ?? 0) + ($order?->collectionDiscountAmount ?? 0))),
            'total_amount' => (int) round((float) ($order?->amount ?? 0)),
            'cancel_state_label' => $this->text($locale, 'cancelled_item_label'),
        ];
    }

    private function mapPrintableItems(array $items, string $locale): array
    {
        return collect($items)->map(function ($item) use ($locale) {
            $qty = (int) ($item['count_item'] ?? $item['count'] ?? 1);
            $price = (float) ($item['item_price'] ?? $item['price'] ?? 0);

            return [
                'title' => (string) ($item['name'] ?? $item['title'] ?? 'Mahsulot'),
                'qty' => $qty,
                'price' => $price,
                'total' => $qty * $price,
                'is_cancelled' => $this->isPrintableCancelledItem($item),
                'cancel_reason' => $this->resolvePrintableCancelReason($item, $locale),
            ];
        })->values()->all();
    }

    private function isPrintableCancelledItem(array $item): bool
    {
        $refundStatus = strtolower(trim((string) ($item['refund_status'] ?? '')));

        return ($item['is_cancelled'] ?? false) === true
            || ! empty($item['cancelled_at'])
            || ! empty($item['cancel_requested_at'])
            || in_array($refundStatus, ['cancel_pending', 'completed'], true);
    }

    private function resolvePrintableCancelReason(array $item, string $locale): ?string
    {
        if (! $this->isPrintableCancelledItem($item)) {
            return null;
        }

        $note = trim((string) ($item['cancel_note_'.$locale] ?? $item['cancel_note_uz'] ?? ''));

        return $note !== '' ? $note : $this->text($locale, 'cancelled_item_label');
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
            return $this->formatUzbekPhone('998'.$digits);
        }

        return Str::startsWith($digits, '+') ? $digits : '+'.$digits;
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
            'ja' => $date->month.'月'.$date->day.'日 '.$date->format('H:i'),
            default => $date->day.'-'.$month.' '.$date->format('H:i'),
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
                'cancelled_item_label' => 'Qolmadi',
                'more_items' => 'yana',
            ],
            'ru' => [
                'customer_fallback' => 'Клиент',
                'card_paid' => 'Оплачено картой',
                'cash_collect' => 'Наличные к получению',
                'delivery_postal' => 'Через почту',
                'delivery_pickup' => 'Самовывоз',
                'delivery_courier' => 'Через курьера',
                'hub_unknown' => 'Хаб не указан',
                'cancelled_item_label' => 'Отменено',
                'more_items' => 'ещё',
            ],
            'en' => [
                'customer_fallback' => 'Customer',
                'card_paid' => 'Paid by card',
                'cash_collect' => 'Cash to collect',
                'delivery_postal' => 'Via post',
                'delivery_pickup' => 'Self pickup',
                'delivery_courier' => 'Via courier',
                'hub_unknown' => 'Hub not set',
                'cancelled_item_label' => 'Cancelled',
                'more_items' => 'more',
            ],
            'ja' => [
                'customer_fallback' => 'お客様',
                'card_paid' => 'カードで支払い済み',
                'cash_collect' => '現金回収あり',
                'delivery_postal' => '郵送でお届け',
                'delivery_pickup' => '店頭受け取り',
                'delivery_courier' => '配達員がお届け',
                'hub_unknown' => 'ハブ未設定',
                'cancelled_item_label' => '欠品',
                'more_items' => '件',
            ],
        ];

        return $map[$locale][$key] ?? $map['uz'][$key] ?? $key;
    }
}
