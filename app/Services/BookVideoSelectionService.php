<?php

namespace App\Services;

use App\Enums\SellerOrderStatusCode;
use App\Models\Books;
use App\Models\BookVideoSet;
use App\Support\ProductImageUrls;
use App\Support\ProductVisibilityScope;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * KITOB VIDEOLARI — haftalik / oylik e'lon videosi uchun kitoblarni tanlash.
 *
 * Avto tanlov: davr oynasida (oxirgi 7 / 30 kun) eng ko'p sotilgan kitoblar;
 * yetmasa umumiy sotuv reytingi, so'ng eng yangi kitoblar bilan to'ldiriladi.
 * Faqat saytda ko'rinadigan (faol, tasdiqlangan, yashirilmagan) kitoblar olinadi.
 */
class BookVideoSelectionService
{
    public const DEFAULT_COUNT = 6;

    /** Joriy davr uchun to'plamni qaytaradi; bo'lmasa avto tanlov bilan yaratadi. */
    public function currentSet(string $period): BookVideoSet
    {
        [$start, $end] = $this->periodRange($period, CarbonImmutable::now());

        $set = BookVideoSet::query()
            ->where('period', $period)
            ->whereDate('period_start', $start->toDateString())
            ->first();

        if ($set) {
            return $set;
        }

        return BookVideoSet::query()->create([
            'period' => $period,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'title' => $this->defaultTitle($period),
            'template' => $period === BookVideoSet::PERIOD_WEEKLY ? 'carousel' : 'countdown',
            'book_ids' => $this->autoPick($period),
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public function periodRange(string $period, CarbonImmutable $at): array
    {
        return $period === BookVideoSet::PERIOD_MONTHLY
            ? [$at->startOfMonth(), $at->endOfMonth()]
            : [$at->startOfWeek(), $at->endOfWeek()];
    }

    public function defaultTitle(string $period): string
    {
        return $period === BookVideoSet::PERIOD_MONTHLY ? 'Oyning top kitoblari' : 'Haftaning top kitoblari';
    }

    /** @return list<int> */
    public function autoPick(string $period, int $count = self::DEFAULT_COUNT): array
    {
        $days = $period === BookVideoSet::PERIOD_MONTHLY ? 30 : 7;
        $since = CarbonImmutable::now()->subDays($days);

        // 1) Davr ichida eng ko'p sotilganlar (bekor qilinmagan buyurtmalar)
        $sold = DB::table('seller_order_items as i')
            ->join('seller_orders as o', 'o.id', '=', 'i.order_id')
            ->where('i.type', 'book')
            ->whereNull('i.cancelled_at')
            ->where('o.created_at', '>=', $since)
            ->where(fn ($q) => $q->whereNull('o.status_code')
                ->orWhere('o.status_code', '!=', SellerOrderStatusCode::CANCELLED->value))
            ->groupBy('i.product_id')
            ->orderByRaw('SUM(i.quantity) DESC')
            ->limit($count * 4)
            ->pluck('i.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // visibleBookIds tartibni saqlamaydi — sotuv reytingi tartibini qayta tiklaymiz
        $visible = array_flip(ProductVisibilityScope::visibleBookIds($sold));
        $ids = array_values(array_filter($sold, fn (int $id) => isset($visible[$id])));

        // 2) Yetmasa — umumiy sotuv reytingi, so'ng eng yangilar
        if (count($ids) < $count) {
            $fill = ProductVisibilityScope::applyBooks(Books::query())
                ->whereNotIn('id', $ids ?: [0])
                ->orderByDesc($period === BookVideoSet::PERIOD_MONTHLY ? 'totalSales' : 'totalSalesWeek')
                ->orderByDesc('totalSales')
                ->orderByDesc('id')
                ->limit($count - count($ids))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_merge($ids, $fill);
        }

        return array_values(array_slice(array_unique($ids), 0, $count));
    }

    /**
     * Videoda chiziladigan kitob kartalari — `ids` tartibida.
     * Rasm boshqaruv domenidagi proksi orqali beriladi (canvas "tainted" bo'lmasligi uchun).
     *
     * @param  list<int>  $ids
     */
    public function cards(array $ids): Collection
    {
        $books = Books::query()
            ->whereIn('id', $ids ?: [0])
            ->get(['id', 'name', 'author', 'price', 'discountPrice', 'images', 'status', 'is_hidden', 'is_approved', 'archived_at'])
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $books->get($id))
            ->filter()
            ->map(fn (Books $b) => [
                'id' => (int) $b->id,
                'name' => (string) $b->name,
                'author' => $b->author,
                'price' => (int) ($b->discountPrice ?: $b->price ?: 0),
                'oldPrice' => ($b->discountPrice && $b->price > $b->discountPrice) ? (int) $b->price : null,
                'hasImage' => $this->firstImageUrl($b) !== null,
                'imageUrl' => route('boshqaruv.book-videos.image', $b->id),
                'visible' => (bool) $b->status && ! $b->is_hidden && (int) $b->is_approved === 1 && $b->archived_at === null,
            ])
            ->values();
    }

    public function firstImageUrl(Books $book): ?string
    {
        $urls = ProductImageUrls::build($book->images);

        return $urls['medium'][0] ?? $urls['original'][0] ?? null;
    }
}
