<?php

namespace App\Support;

use App\Models\BookEdition;
use App\Models\Books;
use App\Models\Seller;
use App\Models\Stationery;

/**
 * Mahsulot web sahifasi ochilmaganda SABABINI aniqlaydi.
 *
 * Mijozga hamma holatda bir xil "Bunday mahsulot mavjud emas" matni
 * ko'rsatiladi, faqat pastida qisqa kod turadi — shu kod orqali admin
 * aniq sababni biladi. Kod formati: KB-{tur}{sabab}, tur: B — kitob,
 * S — kanselyariya. Masalan: KB-B20.
 *
 *   10  Mahsulot bazada yo'q (noto'g'ri ID / artikul yoki butunlay o'chirilgan)
 *   20  Global katalogda sotuvdan olingan (taqiqlangan kitob)
 *   21  Admin arxivlagan (o'chirgan)
 *   30  Do'kon mahsulotni yashirgan (is_hidden)
 *   31  Do'kon sotuvni o'chirgan (status = nofaol)
 *   40  Moderatsiyada — hali tasdiqlanmagan
 *   41  Moderatsiyada rad etilgan
 *   50  Do'kon topilmadi (o'chirilgan)
 *   51  Do'kon tasdiqlanmagan yoki bloklangan
 *   52  Do'kon yashirilgan
 *   53  Filial do'kon — mahsulot asosiy do'kon orqali sotiladi
 *   99  Boshqa / aniqlab bo'lmadi
 */
class ProductUnavailability
{
    public const REASONS = [
        10 => "Mahsulot bazada yo'q",
        20 => 'Global katalogda sotuvdan olingan',
        21 => 'Admin arxivlagan',
        30 => "Do'kon yashirgan",
        31 => "Do'kon sotuvni o'chirgan",
        40 => 'Moderatsiyada',
        41 => 'Moderatsiyada rad etilgan',
        50 => "Do'kon topilmadi",
        51 => "Do'kon tasdiqlanmagan yoki bloklangan",
        52 => "Do'kon yashirilgan",
        53 => 'Filial do\'kon',
        99 => 'Aniqlanmadi',
    ];

    /** @return array{code: string, reason: int} */
    public static function forBook(?int $id = null, ?string $artikul = null): array
    {
        try {
            $book = $id !== null
                ? Books::query()->find($id)
                : Books::query()->where('artikul', $artikul)->first();

            return self::make('B', $book ? self::bookReason($book) : 10);
        } catch (\Throwable) {
            return self::make('B', 99);
        }
    }

    /** @return array{code: string, reason: int} */
    public static function forStationery(?int $id = null, ?string $artikul = null): array
    {
        try {
            $item = $id !== null
                ? Stationery::query()->find($id)
                : Stationery::query()->where('artikul', $artikul)->first();

            return self::make('S', $item ? self::baseReason($item) : 10);
        } catch (\Throwable) {
            return self::make('S', 99);
        }
    }

    /** Artikul bo'yicha: avval kitob, keyin kanselyariya qidiriladi. */
    public static function forArtikul(string $artikul): array
    {
        try {
            if (Books::query()->where('artikul', $artikul)->exists()) {
                return self::forBook(null, $artikul);
            }
            if (Stationery::query()->where('artikul', $artikul)->exists()) {
                return self::forStationery(null, $artikul);
            }
        } catch (\Throwable) {
            return self::make('B', 99);
        }

        return self::make('B', 10);
    }

    private static function bookReason(Books $book): int
    {
        $state = $book->archived_state;
        if (is_string($state)) {
            $state = json_decode($state, true);
        }
        if (is_array($state) && ! empty($state['banned_at'])) {
            return 20;
        }
        if ($book->edition_id) {
            $edition = BookEdition::withTrashed()->find($book->edition_id, ['id', 'status']);
            if ($edition && $edition->status === BookEdition::STATUS_REJECTED) {
                return 20;
            }
        }
        if ($book->archived_at) {
            return 21;
        }

        return self::baseReason($book);
    }

    private static function baseReason(Books|Stationery $p): int
    {
        $seller = Seller::query()->find($p->seller_id, ['id', 'status', 'is_hidden', 'parent_id']);
        if (! $seller) {
            return 50;
        }
        if ($seller->status !== 'approved') {
            return 51;
        }
        if ((int) $seller->is_hidden === 1) {
            return 52;
        }
        if ((int) ($seller->parent_id ?? 0) > 0) {
            return 53;
        }
        if ((int) $p->is_hidden === 1) {
            return 30;
        }
        if (! $p->status) {
            return 31;
        }
        if ((int) $p->is_approved === 2) {
            return 41;
        }
        if ((int) $p->is_approved !== 1) {
            return 40;
        }

        return 99;
    }

    private static function make(string $type, int $reason): array
    {
        return ['code' => "KB-{$type}{$reason}", 'reason' => $reason];
    }

    /** "Bunday mahsulot mavjud emas" sahifasi (HTTP 404). */
    public static function response(array $diag, ?int $id = null)
    {
        return response()->view('errors.product-unavailable', [
            'code' => $diag['code'],
            'productId' => $id,
        ], 404)->header('X-Robots-Tag', 'noindex');
    }
}
