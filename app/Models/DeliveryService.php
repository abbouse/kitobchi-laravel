<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryService extends Model
{
    protected $fillable = [
        'name',
        'type',
        'priceKg',
        'muddat',
        'forCountry',
        'capital',
        'freePriceFrom',
        'status',
    ];

    protected $casts = [
        'capital'      => 'boolean',
        'status'       => 'boolean',
        'priceKg'      => 'integer',
        'muddat'       => 'integer',
        'freePriceFrom'=> 'integer',
    ];

    // ── Faqat faol xizmatlar ──────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // ── Mamlakat bo'yicha ─────────────────────────────────────────────
    public function scopeForCountry($query, string $country = 'uzbekistan')
    {
        return $query->where('forCountry', $country);
    }

    // ── Toshkent uchun (capital=true ham, false ham) ───────────────────
    // ── Viloyat uchun (faqat capital=false) ───────────────────────────
    public function scopeForRegion($query, bool $isTashkent)
    {
        if (!$isTashkent) {
            $query->where('capital', false);
        }
        return $query;
    }
}