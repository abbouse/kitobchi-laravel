<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Books;
use Illuminate\Support\Collection;

/**
 * QOLDIQNI KOD BO'YICHA TOPISH (skaner, do'kon API va hamkor API uchun bitta joy).
 *
 * Global katalogdan keyin bitta ISBN bitta taklifni bildirmaydi: bir xil ISBN
 * bilan qattiq va yumshoq muqovali nashrlar alohida kartada bo'ladi, do'konda
 * esa ikkalasi ham turishi mumkin. Ilgari `whereIsbn(...)->first()` shu holatda
 * tasodifiy taklifni tanlab, noto'g'ri kitobning qoldig'ini yozardi.
 *
 * Shuningdek arxivlangan (admin o'chirgan) takliflar chetlab o'tiladi — ular
 * buyurtma tarixi uchun saqlanadi, sotuvda emas.
 *
 * Kod 8 xonali bo'lsa — artikul (`ProductArtikul`: 2 prefiks + 6 tartib raqam),
 * aks holda ISBN. ISBN 10/13 xonali bo'lgani uchun to'qnashuv yo'q.
 */
class StockCodeLookup
{
    /**
     * @return array{book: ?Books, matches: Collection<int, Books>, reason: ?string}
     */
    public static function findSellerBook(int $sellerId, string $code): array
    {
        $code = trim($code);

        $query = Books::query()
            ->where('seller_id', $sellerId)
            ->whereNull('archived_at');

        if (preg_match('/^\d{8}$/', $code) === 1) {
            $book = (clone $query)->where('artikul', $code)->first();

            return ['book' => $book, 'matches' => collect(), 'reason' => $book ? null : 'not_found'];
        }

        $canonical = Books::normalizeIsbn($code);
        if ($canonical === null) {
            return ['book' => null, 'matches' => collect(), 'reason' => 'invalid_isbn'];
        }

        $matches = $query->with('edition')->whereIsbn($canonical)->orderBy('id')->get();

        if ($matches->isEmpty()) {
            return ['book' => null, 'matches' => $matches, 'reason' => 'not_found'];
        }

        if ($matches->count() > 1) {
            // Bir xil ISBN — turli nashr. Tasodifiy tanlamaymiz: chaqiruvchi
            // artikul yuborishi kerak.
            return ['book' => null, 'matches' => $matches, 'reason' => 'ambiguous'];
        }

        return ['book' => $matches->first(), 'matches' => $matches, 'reason' => null];
    }

    /**
     * Noaniqlik javobida ko'rsatiladigan variantlar ro'yxati.
     *
     * @param  Collection<int, Books>  $matches
     * @return array<int, array<string, mixed>>
     */
    public static function candidates(Collection $matches): array
    {
        return $matches->map(fn (Books $b) => [
            'id' => (int) $b->id,
            'artikul' => $b->artikul,
            'name' => $b->name,
            'variant' => $b->edition ? CatalogOffers::variantLabel($b->edition) : '',
        ])->values()->all();
    }
}
