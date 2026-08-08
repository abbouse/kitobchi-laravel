<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Seller;
use App\Models\SellerCommissionPromotion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class SellerCommissionService
{
    /**
     * Resolve both the permanent base rule and a temporary promotion.
     * The date is explicit so an order keeps the commercial terms that were
     * valid when that order was created, even if settlement happens later.
     *
     * @return array<string, mixed>
     */
    public function resolve(Seller $seller, int $grossAmount, Carbon|string|null $at = null): array
    {
        $ruleDate = $at instanceof Carbon ? $at->copy() : Carbon::parse($at ?? now());
        $base = $this->resolveBase($seller, $grossAmount);
        $promotion = $this->effectivePromotion($seller, $ruleDate);
        $effectivePercent = self::effectivePercent((int) $base['percent'], $promotion?->type, $promotion?->value);
        $basePrice = (int) round($grossAmount * (int) $base['percent'] / 100);
        $commissionPrice = (int) round($grossAmount * $effectivePercent / 100);

        return [
            'base_percent' => (int) $base['percent'],
            'effective_percent' => $effectivePercent,
            'base_price' => $basePrice,
            'commission_price' => $commissionPrice,
            'benefit_amount' => max(0, $basePrice - $commissionPrice),
            'base_source' => $base['source'],
            'source' => $promotion ? 'promotion' : $base['source'],
            'global_rule' => $base['global_rule'],
            'promotion' => $promotion,
            'rule_date' => $ruleDate,
        ];
    }

    /**
     * @return array{percent:int, source:string, global_rule:?array<string, int>}
     */
    public function resolveBase(Seller $seller, int $grossAmount): array
    {
        if ($seller->commission_percent !== null) {
            return [
                'percent' => max(0, min(100, (int) $seller->commission_percent)),
                'source' => 'individual',
                'global_rule' => null,
            ];
        }

        $setting = CommissionSetting::query()
            ->where('priceFrom', '<=', $grossAmount)
            ->where(function ($query) use ($grossAmount) {
                $query->whereNull('priceTo')
                    ->orWhere('priceTo', 0)
                    ->orWhere('priceTo', '>=', $grossAmount);
            })
            ->orderByDesc('priceFrom')
            ->first();

        if (! $setting) {
            Log::warning('Commission setting missing for seller order settlement', [
                'seller_id' => $seller->id,
                'gross_amount' => $grossAmount,
            ]);
        }

        return [
            'percent' => max(0, min(100, (int) ($setting?->percent ?? 0))),
            'source' => $setting ? 'global' : 'missing',
            'global_rule' => $setting ? [
                'id' => (int) $setting->id,
                'from' => (int) $setting->priceFrom,
                'to' => (int) ($setting->priceTo ?? 0),
                'percent' => (int) $setting->percent,
            ] : null,
        ];
    }

    public function effectivePromotion(Seller $seller, Carbon|string|null $at = null): ?SellerCommissionPromotion
    {
        if (! Schema::hasTable('seller_commission_promotions')) {
            return null;
        }

        $date = $at instanceof Carbon ? $at : Carbon::parse($at ?? now());

        return SellerCommissionPromotion::query()
            ->where('seller_id', $seller->id)
            ->effectiveAt($date)
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function grant(
        Seller $seller,
        string $type,
        int $value,
        Carbon $startsAt,
        Carbon $endsAt,
        string $reason,
        ?string $notes = null,
        ?int $createdBy = null,
    ): SellerCommissionPromotion {
        if (! in_array($type, [SellerCommissionPromotion::TYPE_FREE, SellerCommissionPromotion::TYPE_FIXED_RATE], true)) {
            throw new InvalidArgumentException('Unknown seller commission promotion type.');
        }
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('Seller commission promotion end must be after its start.');
        }

        SellerCommissionPromotion::query()
            ->where('seller_id', $seller->id)
            ->whereNull('revoked_at')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->update(['revoked_at' => $startsAt, 'updated_at' => now()]);

        return SellerCommissionPromotion::create([
            'seller_id' => $seller->id,
            'type' => $type,
            'value' => $type === SellerCommissionPromotion::TYPE_FREE ? 0 : max(0, min(100, $value)),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $reason,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    public function revoke(Seller $seller, Carbon|string|null $at = null): int
    {
        $date = $at instanceof Carbon ? $at : Carbon::parse($at ?? now());

        return SellerCommissionPromotion::query()
            ->where('seller_id', $seller->id)
            ->whereNull('revoked_at')
            ->where('ends_at', '>', $date)
            ->update(['revoked_at' => $date, 'updated_at' => now()]);
    }

    public static function effectivePercent(int $basePercent, ?string $promotionType, ?int $promotionValue): int
    {
        $basePercent = max(0, min(100, $basePercent));

        return match ($promotionType) {
            SellerCommissionPromotion::TYPE_FREE => 0,
            SellerCommissionPromotion::TYPE_FIXED_RATE => min($basePercent, max(0, min(100, (int) $promotionValue))),
            default => $basePercent,
        };
    }
}
