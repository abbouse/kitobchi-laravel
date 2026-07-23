<?php

namespace App\Models\Concerns;

use App\Models\BranchStock;
use App\Services\BranchStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mahsulot modellariga filial-darajali stock qobiliyatini beradi.
 *
 * Legacy `count`/`stock` USTUNLARI O'CHIRILGAN — bu trait ularni accessor
 * sifatida qayta tiriltiradi (barcha filiallar yig'indisi), shuning uchun
 * $product->count / $product->stock o'qiydigan eski kod va API javoblari
 * o'zgarishsiz ishlayveradi.
 *
 * Model talablari: branchStockType(): string qaytarishi kerak.
 */
trait HasBranchStock
{
    /** book | stationery | gift */
    abstract public function branchStockType(): string;

    /** Mahsulot darajasidagi (variant_id = 0) filial qatorlari. */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'product_id')
            ->where('product_type', $this->branchStockType())
            ->where('variant_id', 0);
    }

    /** Variantlar bilan birga barcha filial qatorlari. */
    public function allBranchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'product_id')
            ->where('product_type', $this->branchStockType());
    }

    /**
     * Barcha filiallar bo'yicha mavjud (quantity - reserved) yig'indi.
     * withSum('branchStocks as branch_available_total', ...) bilan eager
     * yuklangan bo'lsa qo'shimcha so'rovsiz ishlaydi.
     */
    public function totalAvailableStock(): int
    {
        if (array_key_exists('branch_available_total', $this->attributes)) {
            return max(0, (int) $this->attributes['branch_available_total']);
        }

        return app(BranchStockService::class)
            ->totalAvailable($this->branchStockType(), (int) $this->id);
    }

    /**
     * PERFORMANCE: ro'yxat endpointlarida accessor N+1 SUM so'rovlarini
     * kesish uchun — jami mavjud stockni bitta subselect bilan yuklaydi.
     * Accessor `branch_available_total` atributini avtomatik ishlatadi.
     */
    public function scopeWithAvailableTotal(Builder $query): Builder
    {
        if (is_null($query->getQuery()->columns)) {
            $query->select($this->getTable() . '.*');
        }

        $sql = BranchStock::availableSql($this->branchStockType(), $this->getTable() . '.id');

        return $query->addSelect(
            \Illuminate\Support\Facades\DB::raw("{$sql} as branch_available_total")
        );
    }

    // ── Query scope'lar (legacy where('count'...) o'rnini bosadi) ─────────

    public function scopeWhereStockAvailable(Builder $query, string $operator, int $value): Builder
    {
        // '>= 0' — variant qatorlarini ham qamrab oladi (jami stock)
        $sql = BranchStock::availableSql($this->branchStockType(), $this->getTable() . '.id', '>= 0');

        return $query->whereRaw("{$sql} {$operator} ?", [$value]);
    }

    public function scopeInStock(Builder $query): Builder
    {
        // EXISTS — SUM subquery'dan tezroq (indeks: product_type, product_id)
        $type = $this->branchStockType();
        $table = $this->getTable();

        return $query->whereExists(function ($q) use ($type, $table) {
            $q->selectRaw('1')
                ->from('branch_stocks as bs')
                ->whereColumn('bs.product_id', "{$table}.id")
                ->where('bs.product_type', $type)
                ->whereRaw('bs.quantity - bs.reserved > 0');
        });
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereStockAvailable('<=', 0);
    }

    public function scopeStockBetween(Builder $query, int $min, int $max): Builder
    {
        $sql = BranchStock::availableSql($this->branchStockType(), $this->getTable() . '.id', '>= 0');

        return $query->whereRaw("{$sql} BETWEEN ? AND ?", [$min, $max]);
    }

    public function scopeOrderByStock(Builder $query, string $direction = 'asc'): Builder
    {
        $sql = BranchStock::availableSql($this->branchStockType(), $this->getTable() . '.id', '>= 0');
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderByRaw("{$sql} {$direction}");
    }
}
