<?php

namespace App\Enums;

enum SellerOrderStatusCode: string
{
    case PAYMENT_PENDING = 'payment_pending';
    case NEW = 'new';
    case ACCEPTED = 'accepted';
    case HANDED_TO_COURIER = 'handed_to_courier';
    case CANCELLED = 'cancelled';

    public static function fromLegacy(string|int|null $value): self
    {
        return match ((string) $value) {
            '0', 'payment_pending' => self::PAYMENT_PENDING,
            '1', 'new' => self::NEW,
            '2', 'accepted' => self::ACCEPTED,
            '3', 'handed_to_courier' => self::HANDED_TO_COURIER,
            '4', 'cancelled' => self::CANCELLED,
            default => self::NEW,
        };
    }

    public function legacy(): int
    {
        return match ($this) {
            self::PAYMENT_PENDING => 0,
            self::NEW => 1,
            self::ACCEPTED => 2,
            self::HANDED_TO_COURIER => 3,
            self::CANCELLED => 4,
        };
    }
}
