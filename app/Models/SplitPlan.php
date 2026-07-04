<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SplitPlan extends Model
{
    public const PERIOD_MONTH = 'month';

    public const PERIOD_WEEK = 'week';

    protected $fillable = [
        'name',
        'months',
        'period_unit',
        'period_every',
        'monthly_interest_percent',
        'min_order_sum',
        'max_order_sum',
        'min_confidence_score',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'monthly_interest_percent' => 'decimal:2',
        'min_confidence_score' => 'decimal:2',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(SplitContract::class, 'plan_id');
    }

    /**
     * Umumiy ustama foizi (marketplace uslubi): oylik % * muddat (oy).
     */
    public function totalInterestPercent(): float
    {
        return round((float) $this->monthly_interest_percent * (int) $this->months, 2);
    }

    /**
     * Tarif bo'yicha installmentlar soni.
     * month: har `period_every` oyda bitta; week: 1 oy = 4 hafta deb olinadi.
     */
    public function installmentsCount(): int
    {
        $months = max(1, (int) $this->months);
        $every = max(1, (int) $this->period_every);

        if ($this->period_unit === self::PERIOD_WEEK) {
            return max(1, intdiv($months * 4, $every));
        }

        return max(1, (int) ceil($months / $every));
    }

    public function frequencyLabel(): string
    {
        $every = max(1, (int) $this->period_every);

        if ($this->period_unit === self::PERIOD_WEEK) {
            return $every === 1 ? 'har hafta' : "har {$every} haftada";
        }

        return $every === 1 ? 'har oy' : "har {$every} oyda";
    }
}
