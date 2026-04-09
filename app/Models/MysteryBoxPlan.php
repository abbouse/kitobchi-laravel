<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MysteryBoxPlan extends Model
{
    protected $fillable = [
        'name_uz', 'name_ru', 'name_en', 'name_ja', 'months', 'price_uzs',
        'books_per_month', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'months'          => 'integer',
        'price_uzs'       => 'integer',
        'books_per_month' => 'integer',
        'sort_order'      => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(MysteryBoxSubscription::class, 'plan_id');
    }

    public function getPricePerMonthAttribute(): int
    {
        return $this->months > 0 ? (int)($this->price_uzs / $this->months) : 0;
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
