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
            'uz' => 'Pochta qabul qildi',
            'ru' => 'Принято почтой',
            'en' => 'Accepted by post',
            'ja' => '郵便局が受け付けました',
        ],
        'in_sorting_facility' => [
            'uz' => 'Saralash markazida',
            'ru' => 'В сортировочном центре',
            'en' => 'At sorting center',
            'ja' => '仕分けセンター',
        ],
        'in_transit' => [
            'uz' => 'Manzil tomon yo‘lda',
            'ru' => 'В пути',
            'en' => 'In transit',
            'ja' => '輸送中',
        ],
        'held_at_customs' => [
            'uz' => 'Bojxona nazoratida',
            'ru' => 'На таможне',
            'en' => 'At customs',
            'ja' => '税関で確認中',
        ],
        'ready_for_issue' => [
            'uz' => 'Pochtadan olib ketishga tayyor',
            'ru' => 'Готово к выдаче',
            'en' => 'Ready for pickup',
            'ja' => '受け取り可能',
        ],
        'out_for_delivery' => [
            'uz' => 'Yetkazishga chiqdi',
            'ru' => 'Передано в доставку',
            'en' => 'Out for delivery',
            'ja' => '配達中',
        ],
        'issued_to_recipient' => [
            'uz' => 'Qabul qiluvchiga berildi',
            'ru' => 'Вручено получателю',
            'en' => 'Delivered',
            'ja' => '配達完了',
        ],
        'delivery_failed' => [
            'uz' => 'Yetkazib bo‘lmadi',
            'ru' => 'Доставить не удалось',
            'en' => 'Delivery failed',
            'ja' => '配達できません',
        ],
        'return_to_sender' => [
            'uz' => 'Yuboruvchiga qaytmoqda',
            'ru' => 'Возвращается отправителю',
            'en' => 'Returning to sender',
            'ja' => '差出人へ返送中',
        ],
        'returned_to_sender' => [
            'uz' => 'Yuboruvchiga qaytarildi',
            'ru' => 'Возвращено отправителю',
            'en' => 'Returned to sender',
            'ja' => '差出人へ返送済み',
        ],
        'cancelled' => [
            'uz' => 'Jo‘natma bekor qilindi',
            'ru' => 'Отправление отменено',
            'en' => 'Shipment cancelled',
            'ja' => '配送キャンセル',
        ],
        'destroyed' => [
            'uz' => 'Pochta bilan bog‘laning',
            'ru' => 'Свяжитесь с почтой',
            'en' => 'Contact the post office',
            'ja' => '郵便局へお問い合わせください',
        ],
        'unknown' => [
            'uz' => 'Pochta holati yangilandi',
            'ru' => 'Статус обновлён',
            'en' => 'Status updated',
            'ja' => '配送状況を更新',
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
