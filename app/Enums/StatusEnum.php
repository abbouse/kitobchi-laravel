<?php

namespace App\Enums;

enum StatusEnum: string
{
    case NEW = 'A';
    case PACKING = 'P';
    case DRAFT = 'B';
    case PUBLIC = 'C';
    case FAIL = 'F';

    public function getColor(): ?string
    {
        return match ($this) {
            self::NEW => 'info',
            self::PACKING => 'info',
            self::DRAFT => 'gray',
            self::PUBLIC => 'success',
            self::FAIL => 'warning',
        };
    }

    public function toString(): ?string
    {
        return match ($this) {
            self::NEW => 'Kutilmoqda',
            self::PACKING => 'Qadoqlanmoqda',
            self::DRAFT => 'Yo\'lda',
            self::PUBLIC => 'Yetkazildi',
            self::FAIL => 'Bekor qilindi',
        };
    }
}
