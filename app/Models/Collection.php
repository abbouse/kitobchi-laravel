<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Kolleksiya" — dasturiy SEO uchun mavzuiy to'plam sahifasi
 * (masalan "Eng yaxshi detektiv kitoblar"). CollectionsController orqali
 * /kolleksiya/{slug} (Nuxt) sahifasiga xizmat qiladi.
 */
class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'intro',
        'meta_description',
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->orderBy('position');
    }
}
