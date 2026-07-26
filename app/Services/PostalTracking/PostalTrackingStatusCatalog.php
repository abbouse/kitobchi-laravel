<?php

namespace App\Services\PostalTracking;

use App\Enums\OrderStatusCode;
use Illuminate\Support\Str;

final class PostalTrackingStatusCatalog
{
    private const ALIASES = [
        'new' => 'unassigned',
        'new_order' => 'unassigned',
        'order_received' => 'unassigned',
        'accepted' => 'unassigned',
        'sorting' => 'in_sorting_facility',
        'in_sorting_warehouse' => 'in_sorting_facility',
        'at_sorting_facility' => 'in_sorting_facility',
        'transit' => 'in_transit',
        'on_the_way' => 'in_transit',
        'ready_to_pickup' => 'ready_for_issue',
        'ready_for_pickup' => 'ready_for_issue',
        'awaiting_pickup' => 'ready_for_issue',
        'delivered' => 'issued_to_recipient',
        'delivered_to_recipient' => 'issued_to_recipient',
        'received_by_recipient' => 'issued_to_recipient',
        'returning' => 'return_to_sender',
        'returning_to_sender' => 'return_to_sender',
        'returned' => 'returned_to_sender',
    ];

    private const LABELS = [
        'unassigned' => [
            'uz' => 'Jo‘natma pochta tomonidan qabul qilindi',
            'ru' => 'Отправление принято почтой',
            'en' => 'Shipment accepted by the post office',
            'ja' => '郵便局が荷物を受け付けました',
        ],
        'in_sorting_facility' => [
            'uz' => 'Saralash markazida',
            'ru' => 'В сортировочном центре',
            'en' => 'At the sorting facility',
            'ja' => '仕分けセンターに到着しました',
        ],
        'in_transit' => [
            'uz' => 'Manzil tomon yo‘lda',
            'ru' => 'В пути к месту назначения',
            'en' => 'On the way to the destination',
            'ja' => 'お届け先へ輸送中です',
        ],
        'held_at_customs' => [
            'uz' => 'Bojxona nazoratida',
            'ru' => 'На таможенном контроле',
            'en' => 'At customs control',
            'ja' => '税関で確認中です',
        ],
        'ready_for_issue' => [
            'uz' => 'Pochta bo‘limiga yetib bordi, olib ketishga tayyor',
            'ru' => 'Прибыло в почтовое отделение и готово к выдаче',
            'en' => 'Arrived at the post office and ready for pickup',
            'ja' => '郵便局に到着し、受け取り可能です',
        ],
        'out_for_delivery' => [
            'uz' => 'Yetkazish uchun yo‘lga chiqdi',
            'ru' => 'Передано на доставку получателю',
            'en' => 'Out for delivery',
            'ja' => '配達に出発しました',
        ],
        'issued_to_recipient' => [
            'uz' => 'Qabul qiluvchiga topshirildi',
            'ru' => 'Вручено получателю',
            'en' => 'Delivered to the recipient',
            'ja' => '受取人に配達されました',
        ],
        'delivery_failed' => [
            'uz' => 'Yetkazib berishning imkoni bo‘lmadi',
            'ru' => 'Не удалось вручить отправление',
            'en' => 'Delivery attempt was unsuccessful',
            'ja' => '配達できませんでした',
        ],
        'return_to_sender' => [
            'uz' => 'Jo‘natma yuboruvchiga qaytarilmoqda',
            'ru' => 'Отправление возвращается отправителю',
            'en' => 'Shipment is returning to the sender',
            'ja' => '差出人へ返送中です',
        ],
        'returned_to_sender' => [
            'uz' => 'Jo‘natma yuboruvchiga qaytarildi',
            'ru' => 'Отправление возвращено отправителю',
            'en' => 'Shipment returned to the sender',
            'ja' => '差出人へ返送されました',
        ],
        'cancelled' => [
            'uz' => 'Pochta jo‘natmasi bekor qilindi',
            'ru' => 'Почтовое отправление отменено',
            'en' => 'Postal shipment cancelled',
            'ja' => '郵便配送がキャンセルされました',
        ],
        'destroyed' => [
            'uz' => 'Jo‘natma bo‘yicha pochta bilan bog‘laning',
            'ru' => 'Свяжитесь с почтой по поводу отправления',
            'en' => 'Please contact the post office about the shipment',
            'ja' => '荷物について郵便局へお問い合わせください',
        ],
        'unknown' => [
            'uz' => 'Pochta holati yangilandi',
            'ru' => 'Статус почтового отправления обновлён',
            'en' => 'Postal shipment status updated',
            'ja' => '郵便配送の状況が更新されました',
        ],
    ];

    public function normalize(mixed $statusCode): string
    {
        if (! is_scalar($statusCode)) {
            return 'unknown';
        }

        $normalized = Str::lower(trim((string) $statusCode));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            return 'unknown';
        }

        return self::ALIASES[$normalized] ?? $normalized;
    }

    /**
     * @return array{uz: string, ru: string, en: string, ja: string}
     */
    public function labels(mixed $statusCode): array
    {
        $code = $this->normalize($statusCode);

        return self::LABELS[$code] ?? self::LABELS['unknown'];
    }

    public function step(mixed $statusCode): string
    {
        return in_array($this->normalize($statusCode), [
            'ready_for_issue',
            'out_for_delivery',
            'issued_to_recipient',
            'delivery_failed',
            'return_to_sender',
            'returned_to_sender',
            'cancelled',
            'destroyed',
        ], true) ? 'handoff' : 'in_transit';
    }

    public function isTerminal(mixed $statusCode): bool
    {
        return in_array($this->normalize($statusCode), [
            'issued_to_recipient',
            'returned_to_sender',
            'cancelled',
            'destroyed',
        ], true);
    }

    public function orderStatusTarget(mixed $statusCode): ?OrderStatusCode
    {
        return match ($this->normalize($statusCode)) {
            'ready_for_issue' => OrderStatusCode::DELIVERED,
            'issued_to_recipient' => OrderStatusCode::CUSTOMER_RECEIVED,
            default => null,
        };
    }
}
