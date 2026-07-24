<?php

namespace App\Services;

use App\Models\ProjectSetting;
use App\Models\Seller;
use App\Models\SellerLocation;
use Illuminate\Support\Collection;

/**
 * Do'kon kuryeri (store courier) yordamchi servisi:
 *  - "Tez kuryer" nomi 4 tilda (do'kon kuryeri checkoutda shunday ko'rinadi);
 *  - manzil kuryer zonasiga (poligon) tushishini tekshirish (point-in-polygon);
 *  - do'konda manzilni qoplaydigan faol kuryerlar bor-yo'qligini aniqlash.
 * Hub aralashmaydi — seller → customer to'g'ridan-to'g'ri.
 */
class StoreCourierService
{
    /** Do'kon kuryeri checkout nomi (barcha do'konlar uchun umumiy brend). */
    public const LABELS = [
        'uz' => 'Tez kuryer',
        'ru' => 'Быстрый курьер',
        'en' => 'Fast courier',
        'ja' => 'エクスプレス配送',
    ];

    public function label(?string $locale): string
    {
        $key = in_array($locale, ['uz', 'ru', 'en', 'ja'], true) ? $locale : 'uz';

        return self::LABELS[$key];
    }

    /** Barcha til variantlari (frontendga bir marta uzatish uchun). */
    public function labels(): array
    {
        return self::LABELS;
    }

    /**
     * Nuqta poligon ichidami — ray casting algoritmi.
     * $polygon = [[lat, lon], ...]. Kamida 3 nuqta bo'lishi shart.
     */
    public function pointInPolygon(float $lat, float $lon, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $latI = (float) ($polygon[$i][0] ?? 0);
            $lonI = (float) ($polygon[$i][1] ?? 0);
            $latJ = (float) ($polygon[$j][0] ?? 0);
            $lonJ = (float) ($polygon[$j][1] ?? 0);

            $denominator = ($latJ - $latI) ?: 1e-12;
            $intersect = (($latI > $lat) !== ($latJ > $lat))
                && ($lon < ($lonJ - $lonI) * ($lat - $latI) / $denominator + $lonI);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Do'konda shu manzilni (lat, lon) qoplaydigan faol store courierlar.
     *
     * @return Collection<int, \App\Models\Couriers>
     */
    public function coveringCouriers(Seller $seller, float $lat, float $lon): Collection
    {
        return $seller->storeCouriers()
            ->where('status', 'approved')
            ->whereNull('store_courier_hidden_at')
            ->with(['branches' => fn ($query) => $query
                ->select('seller_locations.id', 'seller_locations.seller_id', 'seller_locations.description', 'seller_locations.is_main', 'seller_locations.store_courier_delivery_price')
                ->where('is_deleted', false)
                ->orderByDesc('is_main')
                ->orderBy('id')])
            ->get()
            ->filter(fn ($courier) => is_array($courier->service_area)
                && $this->pointInPolygon($lat, $lon, $courier->service_area))
            ->values();
    }

    /**
     * Manzilni qoplaydigan eng mos filial va uning narxi.
     *
     * @return array{courier: \App\Models\Couriers, branch: SellerLocation, price: int}|null
     */
    public function resolveCoverage(Seller $seller, float $lat, float $lon): ?array
    {
        foreach ($this->coveringCouriers($seller, $lat, $lon) as $courier) {
            $branch = $courier->branches->first();
            if (! $branch) {
                continue;
            }

            return [
                'courier' => $courier,
                'branch' => $branch,
                'price' => $this->priceForBranch($seller, $branch),
            ];
        }

        return null;
    }

    /** Do'kon shu manzilga o'z kuryeri bilan yetkaza oladimi? */
    public function hasCoverage(Seller $seller, float $lat, float $lon): bool
    {
        return $this->resolveCoverage($seller, $lat, $lon) !== null;
    }

    /**
     * "Tez kuryer" umumiy delivery_services qatori (barcha do'kon kuryerlari uchun
     * bitta). deliveryservice_id validatsiyasi (exists:delivery_services,id) uchun
     * kerak — buy vaqtida shu id yuboriladi. Yo'q bo'lsa yaratiladi (idempotent).
     */
    public function deliveryService(): \App\Models\DeliveryService
    {
        return \App\Models\DeliveryService::firstOrCreate(
            ['type' => 'store_courier'],
            [
                'name' => self::LABELS['uz'],
                'status' => true,
                'forCountry' => 'uzbekistan',
                'priceKg' => 0,
                'muddat' => 0,
                'capital' => false,
                'freePriceFrom' => 0,
            ]
        );
    }

    /**
     * Checkout uchun "Tez kuryer" offeri (bitta-do'kon + qamrov bo'lganda).
     * Narx filial darajasidagi flat qiymat (store_courier_delivery_price).
     * `store_courier` bayrog'i orqali downstream (buy + feed) ajratadi.
     */
    public function buildOffer(Seller $seller, ?string $locale, int $deliveryServiceId, ?float $lat = null, ?float $lon = null): array
    {
        $coverage = ($lat !== null && $lon !== null) ? $this->resolveCoverage($seller, $lat, $lon) : null;
        $price = (int) ($coverage['price'] ?? max($this->minimumDeliveryPrice(), (int) ($seller->own_courier_delivery_price ?? 0)));

        return [
            'id' => $deliveryServiceId,
            'name' => $this->label($locale),
            'type' => 'store_courier',
            'store_courier' => true,
            'seller_id' => (int) $seller->id,
            'seller_location_id' => isset($coverage['branch']) ? (int) $coverage['branch']->id : null,
            'muddat' => 0,
            'is_free' => $price === 0,
            'calculated_price' => $price,
            'base_delivery_price' => $price,
            'additional_seller_price' => 0,
            'additional_seller_percent' => 0,
            'seller_count' => 1,
            'cod_allowed' => true,
            'zone_rule_id' => 0,
            'zone_name' => $this->label($locale),
        ];
    }

    public function priceForBranch(Seller $seller, SellerLocation $branch): int
    {
        $price = $branch->store_courier_delivery_price;
        if ($price === null) {
            $price = $seller->own_courier_delivery_price ?? 0;
        }

        return max($this->minimumDeliveryPrice(), max(0, (int) $price));
    }

    public function minimumDeliveryPrice(): int
    {
        return max(0, (int) (ProjectSetting::query()->value('seller_courier_min_delivery_price') ?? 0));
    }
}
