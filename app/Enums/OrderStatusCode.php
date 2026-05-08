<?php

namespace App\Enums;

enum OrderStatusCode: string
{
    case PENDING = 'pending';
    case PACKING = 'packing';
    case IN_DELIVERY = 'in_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    public static function fromLegacy(string|int|null $value): self
    {
        return match ((string) $value) {
            'A', 'pending' => self::PENDING,
            'P', 'packing' => self::PACKING,
            'B', 'in_delivery' => self::IN_DELIVERY,
            'C', 'delivered' => self::DELIVERED,
            'F', 'cancelled' => self::CANCELLED,
            'R', 'returned' => self::RETURNED,
            default => self::PENDING,
        };
    }

    public function legacy(): string
    {
        return match ($this) {
            self::PENDING => 'A',
            self::PACKING => 'P',
            self::IN_DELIVERY => 'B',
            self::DELIVERED => 'C',
            self::CANCELLED => 'F',
            self::RETURNED => 'F',
        };
    }
}
