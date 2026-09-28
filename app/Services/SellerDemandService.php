<?php

namespace App\Services;

use App\Models\Books;
use App\Models\Stationery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mahsulotga mijozlar talabi: sevimlilarda, "kelganda xabar bering"
 * ro'yxatida va savatda nechta mijoz.
 *
 * Kitob uchun sevimli va kutish ro'yxati KITOB bo'yicha (katalog kartasi)
 * sanaladi: mijoz odatda ro'yxatda ko'ringan (boshqa do'konning) taklifini
 * sevimliga qo'shadi — bu do'kon uchun ham shu kitobga talab. Savat esa
 * aynan shu do'kon taklifi bo'yicha.
 */
class SellerDemandService
{
    /**
     * @param  Collection<int, Books|Stationery>  $products  bitta turdagi mahsulotlar
     * @return array<int, array{favourites:int, waiting:int, in_cart:int}>
     */
    public function demandFor(string $type, Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $ids = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $result = array_fill_keys($ids, ['favourites' => 0, 'waiting' => 0, 'in_cart' => 0]);

        $inCart = DB::table('my_carts')
            ->where('product_type', $type)
            ->whereIn('product_id', $ids)
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(DISTINCT user_id) as c')
            ->pluck('c', 'product_id');

        if ($type === 'book') {
            $favourites = $this->bookCounts('favourite_products', $products);
            $waiting = $this->bookCounts('product_stock_alerts', $products, pendingAlertsOnly: true);
        } else {
            $favourites = $this->directCounts('favourite_products', $type, $ids);
            $waiting = $this->directCounts('product_stock_alerts', $type, $ids, pendingAlertsOnly: true);
        }

        foreach ($ids as $id) {
            $result[$id] = [
                'favourites' => (int) ($favourites[$id] ?? 0),
                'waiting' => (int) ($waiting[$id] ?? 0),
                'in_cart' => (int) ($inCart[$id] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Do'konda tugagan, lekin mijozlar kutayotgan mahsulotlar — talab
     * bo'yicha saralangan.
     *
     * @return array{out_of_stock:int, items: list<array<string,mixed>>}
     */
    public function waitingProducts(int $storeSellerId, int $limit = 20): array
    {
        $items = collect();
        $outOfStock = 0;

        foreach (['book' => Books::class, 'stationery' => Stationery::class] as $type => $model) {
            $query = $model::query()
                ->where('seller_id', $storeSellerId)
                ->where('is_hidden', false)
                ->whereStockAvailable('<=', 0);
            if ($type === 'book') {
                $query->whereNull('archived_at');
            }

            $outOfStock += (clone $query)->count();

            $columns = $type === 'book'
                ? ['id', 'name', 'author', 'images', 'price', 'discountPrice', 'edition_id', 'status', 'updated_at']
                : ['id', 'name', 'images', 'price', 'discount_price', 'status', 'updated_at'];

            // Juda katta do'konda ham tez: eng so'nggi 500 ta tugagan mahsulot
            $products = $query->latest('updated_at')->limit(500)->get($columns);
            $demand = $this->demandFor($type, $products);

            foreach ($products as $product) {
                $d = $demand[(int) $product->id] ?? null;
                if (! $d || ($d['favourites'] + $d['waiting'] + $d['in_cart']) === 0) {
                    continue;
                }

                $images = is_array($product->images) ? $product->images : [];
                $items->push([
                    'product_id' => (int) $product->id,
                    'type' => $type,
                    'name' => (string) $product->name,
                    'author' => $type === 'book' ? $product->author : null,
                    'image' => $images[0] ?? null,
                    'price' => (int) (($type === 'book' ? $product->discountPrice : $product->discount_price) ?: $product->price),
                    'active' => (bool) $product->status,
                    'favourites' => $d['favourites'],
                    'waiting' => $d['waiting'],
                    'in_cart' => $d['in_cart'],
                    // Kutish ro'yxati eng kuchli signal (mijoz aynan kelishini kutmoqda)
                    'score' => $d['waiting'] * 3 + $d['in_cart'] * 2 + $d['favourites'],
                ]);
            }
        }

        return [
            'out_of_stock' => $outOfStock,
            'waiting_total' => $items->count(),
            'items' => $items->sortByDesc('score')->take($limit)->values()->all(),
        ];
    }

    /**
     * Kitob bo'yicha: taklif kartaga ulangan bo'lsa — kartaning barcha
     * takliflari, aks holda taklifning o'zi.
     *
     * @return array<int,int> product_id => mijozlar soni
     */
    private function bookCounts(string $table, Collection $books, bool $pendingAlertsOnly = false): array
    {
        $counts = [];

        $linked = $books->filter(fn ($b) => $b->edition_id)->groupBy('edition_id');
        if ($linked->isNotEmpty()) {
            $byEdition = DB::table($table.' as t')
                ->join('books as b', 'b.id', '=', 't.product_id')
                ->where('t.product_type', 'book')
                ->whereIn('b.edition_id', $linked->keys()->all())
                ->when($pendingAlertsOnly, fn ($q) => $q->whereNull('t.notified_at'))
                ->groupBy('b.edition_id')
                ->selectRaw('b.edition_id, COUNT(DISTINCT t.user_id) as c')
                ->pluck('c', 'edition_id');

            foreach ($linked as $editionId => $group) {
                foreach ($group as $book) {
                    $counts[(int) $book->id] = (int) ($byEdition[$editionId] ?? 0);
                }
            }
        }

        $unlinked = $books->filter(fn ($b) => ! $b->edition_id)->pluck('id')->all();
        if ($unlinked !== []) {
            $counts = $this->directCounts($table, 'book', $unlinked, $pendingAlertsOnly) + $counts;
        }

        return $counts;
    }

    /** @return array<int,int> */
    private function directCounts(string $table, string $type, array $ids, bool $pendingAlertsOnly = false): array
    {
        return DB::table($table)
            ->where('product_type', $type)
            ->whereIn('product_id', $ids)
            ->when($pendingAlertsOnly, fn ($q) => $q->whereNull('notified_at'))
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(DISTINCT user_id) as c')
            ->pluck('c', 'product_id')
            ->map(fn ($c) => (int) $c)
            ->all();
    }
}
