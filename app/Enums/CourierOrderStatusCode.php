<?php

namespace App\Enums;

enum CourierOrderStatusCode: string
{
    case PAYMENT_PENDING = 'payment_pending';
    case PENDING = 'pending';
    case IN_DELIVERY = 'in_delivery';
    case DELIVERED = 'delivered';
    case CUSTOMER_RECEIVED = 'customer_received';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    public static function fromLegacy(string|int|null $value): self
    {
        return match ((string) $value) {
            'pay_process', 'payment_pending' => self::PAYMENT_PENDING,
            'pending' => self::PENDING,
            'in_delivery' => self::IN_DELIVERY,
            'delivered' => self::DELIVERED,
            'customer_received' => self::CUSTOMER_RECEIVED,
            'rejected', 'cancelled' => self::CANCELLED,
            'returned' => self::RETURNED,
            default => self::PENDING,
        };
    }

    public function legacy(): string
    {
        return match ($this) {
            self::PAYMENT_PENDING => 'pay_process',
            self::PENDING => 'pending',
            self::IN_DELIVERY => 'in_delivery',
            self::DELIVERED => 'delivered',
            self::CUSTOMER_RECEIVED => 'customer_received',
            self::CANCELLED => 'rejected',
            self::RETURNED => 'returned',
        };
    }
}
