<?php

namespace App\Services;

use App\Models\ProjectSetting;

/**
 * To'plam narxini DETERMINISTIK hisoblaydi — pul matematikasi AI'ga topshirilmaydi.
 *
 * Biznes modeli: kitoblar sellerlarga tegishli. Buyer alohida olsa Σ effektiv narx
 * to'laydi. Platforma har kitobdan komissiya oladi (seller qolganini oladi). To'plam
 * chegirmasini PLATFORMA o'z komissiyasidan qoplaydi (sellerlar to'liq to'lovini oladi),
 * shuning uchun chegirma platforma marginini kamaytiradi. Chegirma margin-poli bilan
 * cheklanadi — sof margin komissiyaning belgilangan % qismidan past tushmaydi.
 */
class CollectionPricingService
{
    /**
     * @param  array<int, array{price:int|float, quantity:int, commission_percent:int|float}>  $books
     * @return array<string, mixed>
     */
    public function calculate(array $books, ?float $targetDiscountPercent = null): array
    {
        $settings = ProjectSetting::query()->first();
        $providerPercent = (float) ($settings->payment_provider_percent ?? 0);
        $taxMode = (string) ($settings->tax_mode ?? 'fixed');
        $taxProfitPercent = (float) ($settings->tax_profit_percent ?? 0);
        $taxFixed = (float) ($settings->tax_fixed_uzs ?? 0);
        $floorPercent = max(0.0, (float) config('collection_advisor.margin_floor_percent', 25));

        $grossRetail = 0.0;
        $totalCommission = 0.0;
        foreach ($books as $book) {
            $qty = max(1, (int) ($book['quantity'] ?? 1));
            $line = max(0, (float) ($book['price'] ?? 0)) * $qty;
            $commission = min(100, max(0, (float) ($book['commission_percent'] ?? 0)));
            $grossRetail += $line;
            $totalCommission += $line * $commission / 100;
        }

        // Berilgan chegirma summasi uchun platforma sof marginini hisoblaydi.
        $evaluate = function (float $discount) use ($grossRetail, $totalCommission, $providerPercent, $taxMode, $taxProfitPercent, $taxFixed): array {
            $bundlePrice = max(0.0, $grossRetail - $discount);
            $platformGross = $totalCommission - $discount;          // platforma chegirmani qoplaydi
            $paymentFee = $bundlePrice * $providerPercent / 100;
            $contributionBeforeTax = $platformGross - $paymentFee;
            $tax = $taxMode === 'profit'
                ? max(0.0, $contributionBeforeTax) * $taxProfitPercent / 100
                : $taxFixed;
            $net = $contributionBeforeTax - $tax;

            return [
                'bundle_price' => $bundlePrice,
                'platform_gross' => $platformGross,
                'payment_fee' => $paymentFee,
                'tax' => $tax,
                'net' => $net,
            ];
        };

        $floor = $totalCommission * $floorPercent / 100;

        // Xavfsiz maksimal chegirma: net >= floor. Net chegirma oshgani sari kamayadi
        // (monoton) — shuning uchun binary search bilan eng katta xavfsiz chegirmani topamiz.
        $maxSafeDiscount = 0.0;
        if ($evaluate(0.0)['net'] >= $floor) {
            $lo = 0.0;
            $hi = $totalCommission; // chegirma komissiyadan oshmasligi kerak
            for ($i = 0; $i < 48; $i++) {
                $mid = ($lo + $hi) / 2;
                if ($evaluate($mid)['net'] >= $floor) {
                    $lo = $mid;
                } else {
                    $hi = $mid;
                }
            }
            $maxSafeDiscount = $lo;
        }

        $targetPct = $targetDiscountPercent;
        if ($targetPct === null) {
            $targetPct = (float) config('collection_advisor.target_discount_max', 20);
        }
        $targetPct = max(0.0, min(90.0, $targetPct));

        $targetDiscount = $grossRetail * $targetPct / 100;
        $appliedDiscount = max(0.0, min($targetDiscount, $maxSafeDiscount));
        $final = $evaluate($appliedDiscount);

        $bundlePrice = (int) round($final['bundle_price']);
        $appliedPct = $grossRetail > 0 ? round($appliedDiscount / $grossRetail * 100, 1) : 0.0;

        return [
            'currency' => 'UZS',
            'gross_retail' => (int) round($grossRetail),
            'total_commission' => (int) round($totalCommission),
            'seller_payout' => (int) round($grossRetail - $totalCommission),
            'target_discount_percent' => round($targetPct, 1),
            'applied_discount_percent' => $appliedPct,
            'applied_discount' => (int) round($appliedDiscount),
            'bundle_price' => $bundlePrice,
            'payment_fee' => (int) round($final['payment_fee']),
            'tax' => (int) round($final['tax']),
            'tax_mode' => $taxMode,
            'platform_net' => (int) round($final['net']),
            'margin_floor' => (int) round($floor),
            'margin_floor_percent' => $floorPercent,
            'max_safe_discount' => (int) round($maxSafeDiscount),
            // AI so'ragan chegirma margin-poliga urilib cheklandi (admin bilishi uchun).
            'discount_clamped' => $targetDiscount > $maxSafeDiscount + 0.5,
        ];
    }
}
