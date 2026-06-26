<?php

namespace App\Enums;

enum PaymentStatusCode: string
{
    case CASH_PENDING = 'cash_pending';
    case CARD_PENDING = 'card_pending';
    case HELD = 'held';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public static function fromLegacy(string|int|null $value): self
    {
        return match ((string) $value) {
            '0', 'cash_pending' => self::CASH_PENDING,
            '1', 'card_pending', 'pending' => self::CARD_PENDING,
            'held', 'hold', 'reserved', 'authorized' => self::HELD,
            '2', 'paid', 'success' => self::PAID,
            '3', 'cancelled', 'rejected' => self::CANCELLED,
            default => self::CASH_PENDING,
        };
    }

    public function legacy(): int
    {
        return match ($this) {
            self::CASH_PENDING => 0,
            self::CARD_PENDING => 1,
            self::HELD => 1,
            self::PAID => 2,
            self::CANCELLED => 3,
        };
    }
}
