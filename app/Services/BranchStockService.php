<?php

namespace App\Services;

use App\Models\BranchStock;
use App\Models\Books;
use App\Models\Gifts;
use App\Models\Stationery;
use App\Models\StationeryVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FILIAL-DARAJALI STOCK — yagona yozish nuqtasi.
 *
 * Qoidalar:
 *  - Har qanday o'zgarish tranzaksiya ichida, qator lockForUpdate bilan.
 *  - Har o'zgarish stock_movements ledger'iga yoziladi.
 *  - quantity hech qachon manfiy bo'lmaydi (UNSIGNED + GREATEST).
 *  - Restock alert + webhook total 0 → >0 o'tishda otiladi (legacy semantika).
 */
class BranchStockService
{
    /** So'rov ichida takroriy SUM so'rovlarini kesish uchun kesh. */
    private array $totalCache = [];

    // =====================================================================
    //  O'QISH
    // =====================================================================

    public function totalAvailable(string $type, int $productId, int $variantId = 0): int
    {
        $key = "{$type}:{$productId}:{$variantId}";
        if (array_key_exists($key, $this->totalCache)) {
            return $this->totalCache[$key];
        }

        $sum = (int) BranchStock::query()
            ->where('product_type', $type)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->sum(DB::raw('quantity - reserved'));

        return $this->totalCache[$key] = max(0, $sum);
    }

    /**
     * N+1 OLDINI OLISH: bir nechta mahsulotning jami stockini BITTA so'rovda
     * yuklab keshga soladi. morphTo yoki loop kontekstlarida (savat, sevimlilar,
     * buyurtma tarixi) `withAvailableTotal()` scope ishlatib bo'lmaganda chaqiriladi.
     * Service singleton bo'lgani uchun kesh accessorlarga ko'rinadi.
     *
     * @param array $items [['type'=>'book','product_id'=>1,'variant_id'=>0], ...]
     */
    public function warmTotals(array $items): void
    {
        // Har (type, variant_id) bo'yicha guruhlab, product_id ro'yxatini yig'amiz
        $byGroup = [];
        foreach ($items as $it) {
            $type = $it['type'] ?? null;
            $pid = (int) ($it['product_id'] ?? 0);
            $vid = (int) ($it['variant_id'] ?? 0);
            if (! $type || $pid <= 0) {
                continue;
            }
            $byGroup[$type . ':' . $vid][$pid] = true;
        }

        foreach ($byGroup as $groupKey => $pidSet) {
            [$type, $vid] = explode(':', $groupKey);
            $pids = array_keys($pidSet);

            // Kesh bor bo'lganlarini chiqarib tashlaymiz
            $pids = array_values(array_filter($pids, fn ($pid) => ! array_key_exists("{$type}:{$pid}:{$vid}", $this->totalCache)));
            if (empty($pids)) {
                continue;
            }

            $rows = BranchStock::query()
                ->where('product_type', $type)
                ->where('variant_id', (int) $vid)
                ->whereIn('product_id', $pids)
                ->groupBy('product_id')
                ->selectRaw('product_id, COALESCE(SUM(quantity - reserved), 0) as total')
                ->pluck('total', 'product_id');

            // So'rovda chiqmagan (stocksiz) mahsulotlar uchun 0 keshlaymiz
            foreach ($pids as $pid) {
                $this->totalCache["{$type}:{$pid}:{$vid}"] = max(0, (int) ($rows[$pid] ?? 0));
            }
        }
    }

    /**
     * Model kolleksiyasidan jami stockni iliqlash (morphTo/loop endpointlar uchun).
     * Books/Stationery/Gifts modellarini avtomatik aniqlaydi.
     */
    public function warmProducts(iterable $products): void
    {
        $items = [];
        foreach ($products as $p) {
            if (! $p || empty($p->id)) {
                continue;
            }
            $type = $p instanceof Books ? 'book'
                : ($p instanceof Gifts ? 'gift' : 'stationery');
            $items[] = ['type' => $type, 'product_id' => (int) $p->id, 'variant_id' => 0];
        }
        if ($items) {
            $this->warmTotals($items);
        }
    }

    /** Variant kolleksiyasidan jami stockni iliqlash. */
    public function warmVariants(iterable $variants): void
    {
        $items = [];
        foreach ($variants as $v) {
            if (! $v || empty($v->id) || empty($v->product_id)) {
                continue;
            }
            $items[] = ['type' => 'stationery', 'product_id' => (int) $v->product_id, 'variant_id' => (int) $v->id];
        }
        if ($items) {
            $this->warmTotals($items);
        }
    }

    /** Mahsulotning filiallar bo'yicha taqsimoti (seller UI uchun). */
    public function branchBreakdown(string $type, int $productId, ?int $variantId = null)
    {
        return BranchStock::query()
            ->with('location:id,fullAddress,is_main,is_deleted')
            ->where('product_type', $type)
            ->where('product_id', $productId)
            ->when($variantId !== null, fn ($q) => $q->where('variant_id', $variantId))
            ->orderByDesc('quantity')
            ->get();
    }

    /** Sellerning default (asosiy) filiali. */
    public function defaultLocationId(int $sellerId): ?int
    {
        $id = DB::table('seller_locations')
            ->where('seller_id', $sellerId)
            ->where('is_deleted', false)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    // =====================================================================
    //  YOZISH — filial darajasida
    // =====================================================================

    /**
     * Filialdagi qatorga delta qo'llash (musbat = kirim, manfiy = chiqim).
     * Qaytaradi: haqiqatda qo'llangan delta (clamp tufayli kichikroq bo'lishi mumkin).
     */
    public function adjustBranch(
        string $type,
        int $productId,
        int $variantId,
        int $sellerId,
        int $locationId,
        int $delta,
        string $reason,
        array $ctx = []
    ): int {
        if ($delta === 0) {
            return 0;
        }

        return DB::transaction(function () use ($type, $productId, $variantId, $sellerId, $locationId, $delta, $reason, $ctx) {
            $oldTotal = $this->freshTotal($type, $productId, $variantId);

            $row = BranchStock::query()
                ->where('product_type', $type)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->where('seller_location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = BranchStock::create([
                    'seller_id' => $sellerId,
                    'seller_location_id' => $locationId,
                    'product_type' => $type,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => 0,
                    'reserved' => 0,
                ]);
            }

            $applied = $delta < 0 ? -min($row->quantity, -$delta) : $delta;
            if ($applied === 0) {
                return 0;
            }

            $row->quantity += $applied;
            $row->save();

            $this->logMovement($row, $applied, $reason, $ctx);
            $this->afterTotalChanged($type, $productId, $variantId, $oldTotal);

            return $applied;
        });
    }

    /** Filialdagi qatorni aniq songa o'rnatish (inventarizatsiya/tahrir). */
    public function setBranchQuantity(
        string $type,
        int $productId,
        int $variantId,
        int $sellerId,
        int $locationId,
        int $quantity,
        string $reason = 'manual_adjust',
        array $ctx = []
    ): void {
        DB::transaction(function () use ($type, $productId, $variantId, $sellerId, $locationId, $quantity, $reason, $ctx) {
            $oldTotal = $this->freshTotal($type, $productId, $variantId);

            $row = BranchStock::query()
                ->where('product_type', $type)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->where('seller_location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = BranchStock::create([
                    'seller_id' => $sellerId,
                    'seller_location_id' => $locationId,
                    'product_type' => $type,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => 0,
                    'reserved' => 0,
                ]);
            }

            $quantity = max(0, $quantity);
            $delta = $quantity - $row->quantity;
            if ($delta === 0) {
                return;
            }

            $row->quantity = $quantity;
            $row->save();

            $this->logMovement($row, $delta, $reason, $ctx);
            $this->afterTotalChanged($type, $productId, $variantId, $oldTotal);
        });
    }

    // =====================================================================
    //  YOZISH — legacy "bitta son" kontrakti (business app hozircha shu)
    // =====================================================================

    /**
     * Eski `count`/`stock` maydonini qabul qiladigan endpointlar uchun:
     * jami stockni yangi songa keltiradi. Farq targetLocation'ga qo'llanadi;
     * kamaytirishda targetdagi qoldiq yetmasa boshqa filiallardan ham olinadi.
     */
    public function setTotalFromLegacy(
        string $type,
        int $productId,
        int $variantId,
        int $sellerId,
        int $newTotal,
        ?int $targetLocationId = null,
        array $ctx = []
    ): void {
        $newTotal = max(0, $newTotal);

        DB::transaction(function () use ($type, $productId, $variantId, $sellerId, $newTotal, $targetLocationId, $ctx) {
            $oldTotal = $this->freshTotal($type, $productId, $variantId);
            $delta = $newTotal - $oldTotal;
            if ($delta === 0) {
                return;
            }

            $locationId = $targetLocationId ?: $this->defaultLocationId($sellerId);
            if (! $locationId) {
                Log::warning('BranchStock: seller has no location, stock edit skipped', [
                    'seller_id' => $sellerId, 'type' => $type, 'product_id' => $productId,
                ]);

                return;
            }

            if ($delta > 0) {
                $this->applyDeltaToBranchLocked($type, $productId, $variantId, $sellerId, $locationId, $delta, 'manual_adjust', $ctx);
            } else {
                $this->drainAcrossBranches($type, $productId, $variantId, $sellerId, -$delta, $locationId, 'manual_adjust', $ctx);
            }

            $this->afterTotalChanged($type, $productId, $variantId, $oldTotal);
        });
    }

    // =====================================================================
    //  SOTUV / QAYTARISH
    // =====================================================================

    /**
     * Sotuvda kamaytirish. preferredLocationId (buyurtma pickup filiali)
     * birinchi, yetmasa qoldiq boshqa filiallardan (quantity DESC) olinadi.
     */
    public function decrementForSale(
        string $type,
        int $productId,
        int $variantId,
        int $quantity,
        ?int $preferredLocationId = null,
        array $ctx = []
    ): int {
        if ($quantity <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($type, $productId, $variantId, $quantity, $preferredLocationId, $ctx) {
            $oldTotal = $this->freshTotal($type, $productId, $variantId);
            $taken = $this->drainAcrossBranches(
                $type, $productId, $variantId, null, $quantity, $preferredLocationId,
                $ctx['reason'] ?? 'sale', $ctx
            );

            if ($taken < $quantity) {
                // MUHIM: avval bu yerda faqat Log::warning yozilib, buyurtma
                // baribir "muvaffaqiyatli" yaratilardi — ya'ni ikkita mijoz
                // oxirgi donaga deyarli bir vaqtda checkout qilsa, ikkalasi
                // ham buyurtma olardi, garchi omborda faqat bittasiga
                // yetadigan mahsulot bo'lsa ham (oversell). Endi bu holatda
                // istisno tashlanadi — bu FUNKSIYA o'z DB::transaction'ini
                // bekor qiladi (shu paytgacha olingan $taken miqdor ham
                // qaytariladi/rollback bo'ladi), va tashqi chaqiruvchi
                // (checkout) butun buyurtmani ham bekor qilishi kerak.
                Log::warning('BranchStock: insufficient stock, aborting decrement', [
                    'type' => $type, 'product_id' => $productId, 'variant_id' => $variantId,
                    'requested' => $quantity, 'taken' => $taken,
                ]);

                throw new \App\Exceptions\InsufficientStockException(
                    $type, $productId, $variantId, $quantity, $taken
                );
            }

            $this->afterTotalChanged($type, $productId, $variantId, $oldTotal);

            return $taken;
        });
    }

    /** Bekor/qaytarish — stockni filialga qaytarish. */
    public function incrementForReturn(
        string $type,
        int $productId,
        int $variantId,
        int $quantity,
        ?int $locationId = null,
        array $ctx = []
    ): void {
        if ($quantity <= 0) {
            return;
        }

        $sellerId = $this->resolveSellerId($type, $productId);
        if (! $sellerId) {
            return;
        }

        $locationId = $locationId ?: $this->defaultLocationId($sellerId);
        if (! $locationId) {
            return;
        }

        $this->adjustBranch(
            $type, $productId, $variantId, $sellerId, $locationId, $quantity,
            $ctx['reason'] ?? 'cancel_return', $ctx
        );
    }

    /**
     * Buyurtma uchun pickup filialini tanlash:
     * eng ko'p itemni qoplaydigan filial → mijozga eng yaqini → main.
     *
     * @param array $items [['type' =>, 'product_id' =>, 'variant_id' =>, 'quantity' =>], ...]
     */
    public function chooseFulfillmentLocation(int $sellerId, array $items, ?float $userLat = null, ?float $userLon = null): ?object
    {
        $locations = DB::table('seller_locations')
            ->where('seller_id', $sellerId)
            ->where('is_deleted', false)
            ->get(['id', 'fullAddress', 'lat', 'lon', 'is_main']);

        if ($locations->isEmpty()) {
            return null;
        }

        if ($locations->count() === 1 || empty($items)) {
            return $locations->sortByDesc('is_main')->first();
        }

        $best = null;
        $bestScore = -1;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($locations as $loc) {
            $covered = 0;
            foreach ($items as $item) {
                $available = (int) BranchStock::query()
                    ->where('product_type', $item['type'])
                    ->where('product_id', (int) $item['product_id'])
                    ->where('variant_id', (int) ($item['variant_id'] ?? 0))
                    ->where('seller_location_id', $loc->id)
                    ->value(DB::raw('quantity - reserved'));

                if ($available >= (int) $item['quantity']) {
                    $covered++;
                }
            }

            $distance = ($userLat !== null && $userLon !== null && $loc->lat && $loc->lon)
                ? $this->haversineKm($userLat, $userLon, (float) $loc->lat, (float) $loc->lon)
                : PHP_FLOAT_MAX;

            $better = $covered > $bestScore
                || ($covered === $bestScore && $distance < $bestDistance)
                || ($covered === $bestScore && $distance === $bestDistance && $loc->is_main);

            if ($better) {
                $best = $loc;
                $bestScore = $covered;
                $bestDistance = $distance;
            }
        }

        return $best;
    }

    // =====================================================================
    //  ICHKI
    // =====================================================================

    /** Lock bilan bitta filialga musbat delta. */
    private function applyDeltaToBranchLocked(
        string $type, int $productId, int $variantId, int $sellerId,
        int $locationId, int $delta, string $reason, array $ctx
    ): void {
        $row = BranchStock::query()
            ->where('product_type', $type)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('seller_location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            $row = BranchStock::create([
                'seller_id' => $sellerId,
                'seller_location_id' => $locationId,
                'product_type' => $type,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => 0,
                'reserved' => 0,
            ]);
        }

        $row->quantity += $delta;
        $row->save();
        $this->logMovement($row, $delta, $reason, $ctx);
    }

    /**
     * needed miqdorni filiallardan yig'ib kamaytirish.
     * preferred filial birinchi, keyin quantity DESC tartibida.
     */
    private function drainAcrossBranches(
        string $type, int $productId, int $variantId, ?int $sellerId,
        int $needed, ?int $preferredLocationId, string $reason, array $ctx
    ): int {
        $rows = BranchStock::query()
            ->where('product_type', $type)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('quantity', '>', 0)
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->orderByRaw('seller_location_id = ? DESC', [$preferredLocationId ?: 0])
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        $taken = 0;
        foreach ($rows as $row) {
            if ($needed <= 0) {
                break;
            }

            $take = min($row->quantity, $needed);
            if ($take <= 0) {
                continue;
            }

            $row->quantity -= $take;
            $row->save();
            $this->logMovement($row, -$take, $reason, $ctx);

            $needed -= $take;
            $taken += $take;
        }

        return $taken;
    }

    private function logMovement(BranchStock $row, int $delta, string $reason, array $ctx): void
    {
        StockMovement::create([
            'branch_stock_id' => $row->id,
            'delta' => $delta,
            'quantity_after' => $row->quantity,
            'reason' => $reason,
            'actor_type' => $ctx['actor_type'] ?? 'system',
            'actor_id' => $ctx['actor_id'] ?? null,
            'ref_type' => $ctx['ref_type'] ?? null,
            'ref_id' => $ctx['ref_id'] ?? null,
            'note' => $ctx['note'] ?? null,
        ]);
    }

    /** Keshsiz jami (tranzaksiya ichida ishlatiladi). */
    private function freshTotal(string $type, int $productId, int $variantId): int
    {
        unset($this->totalCache["{$type}:{$productId}:{$variantId}"]);

        return (int) BranchStock::query()
            ->where('product_type', $type)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->sum(DB::raw('quantity - reserved'));
    }

    /**
     * Total o'zgargach: kesh tozalash + restock alert + webhook.
     * Legacy semantika: alert faqat 0 → >0 o'tishda.
     */
    private function afterTotalChanged(string $type, int $productId, int $variantId, int $oldTotal): void
    {
        unset($this->totalCache["{$type}:{$productId}:{$variantId}"]);
        $newTotal = $this->freshTotal($type, $productId, $variantId);

        if ($newTotal === $oldTotal) {
            return;
        }

        try {
            $alerts = app(ProductStockAlertService::class);

            if ($type === 'book') {
                $book = Books::find($productId);
                if ($book) {
                    $alerts->notifyBookRestocked($book, $oldTotal, $newTotal);
                    $this->emitStockWebhook('book', $book->id, (string) $book->name, (int) ($book->price ?? 0), $newTotal);
                }
            } elseif ($type === 'stationery') {
                $product = Stationery::find($productId);
                if ($product) {
                    if ($variantId > 0) {
                        $variant = StationeryVariant::find($variantId);
                        if ($variant) {
                            $alerts->notifyVariantRestocked($variant, $oldTotal, $newTotal);
                        }
                    } else {
                        $alerts->notifyStationeryRestocked($product, $oldTotal, $newTotal);
                    }
                    $this->emitStockWebhook('stationery', $product->id, (string) $product->name, (int) ($product->price ?? 0), $newTotal);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('BranchStock afterTotalChanged side-effects failed: ' . $e->getMessage());
        }
    }

    private function emitStockWebhook(string $type, int $id, string $name, int $price, int $total): void
    {
        $webhooks = app(WebhookService::class);
        if (! $webhooks->hasActiveWebhooks()) {
            return;
        }

        $webhooks->dispatch('product.stock_changed', [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'price' => $price,
            'count' => $total,
            'in_stock' => $total > 0,
        ]);
    }

    private function resolveSellerId(string $type, int $productId): ?int
    {
        $table = match ($type) {
            'book' => 'books',
            'stationery' => 'stationeries',
            'gift' => 'gifts',
            default => null,
        };

        if (! $table) {
            return null;
        }

        $sellerId = DB::table($table)->where('id', $productId)->value('seller_id');

        return $sellerId ? (int) $sellerId : null;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
