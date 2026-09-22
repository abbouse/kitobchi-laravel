<?php

namespace App\Support;

/**
 * ISBN yordamchilari: tozalash, checksum tekshiruvi, ISBN-10 → ISBN-13.
 *
 * Katalog shu klass orqali ISBN'ni YAGONA kanonik ko'rinishga (ISBN-13)
 * keltiradi: "978-9943-08-123-1", "9943081238", "isbn 9789943081231" —
 * hammasi bitta kitobni topadi. Checksum xato raqam (skaner noto'g'ri
 * o'qigan yoki qo'lda xato yozilgan) hech qachon katalog kaliti bo'lmaydi.
 */
final class Isbn
{
    public static function clean(?string $raw): string
    {
        if ($raw === null) {
            return '';
        }

        return strtoupper(preg_replace('/[^0-9Xx]/', '', $raw) ?? '');
    }

    public static function isValid13(string $digits): bool
    {
        if (! preg_match('/^97[89]\d{10}$/', $digits)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10 === (int) $digits[12];
    }

    public static function isValid10(string $digits): bool
    {
        if (! preg_match('/^\d{9}[\dX]$/', $digits)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $value = $digits[$i] === 'X' ? 10 : (int) $digits[$i];
            $sum += $value * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    /** Har qanday yozuvdan to'g'ri ISBN-13 (yoki null). */
    public static function toIsbn13(?string $raw): ?string
    {
        $digits = self::clean($raw);

        if (strlen($digits) === 13) {
            return self::isValid13($digits) ? $digits : null;
        }

        if (strlen($digits) === 10 && self::isValid10($digits)) {
            $core = '978' . substr($digits, 0, 9);
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $core[$i] * ($i % 2 === 0 ? 1 : 3);
            }

            return $core . ((10 - ($sum % 10)) % 10);
        }

        return null;
    }

    /** 978 prefiksli ISBN-13 uchun ISBN-10 (979 uchun mavjud emas). */
    public static function toIsbn10(?string $isbn13): ?string
    {
        $digits = self::clean($isbn13);
        if (! self::isValid13($digits) || ! str_starts_with($digits, '978')) {
            return null;
        }

        $core = substr($digits, 3, 9);
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $core[$i] * (10 - $i);
        }
        $check = (11 - ($sum % 11)) % 11;

        return $core . ($check === 10 ? 'X' : (string) $check);
    }

    /** Foydalanuvchiga ko'rsatiladigan xato turi. */
    public static function problem(?string $raw): ?string
    {
        $digits = self::clean($raw);
        if ($digits === '') {
            return 'empty';
        }
        if (! in_array(strlen($digits), [10, 13], true)) {
            return 'length';
        }

        return self::toIsbn13($digits) === null ? 'checksum' : null;
    }
}
