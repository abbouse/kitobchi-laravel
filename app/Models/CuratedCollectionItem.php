<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuratedCollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'section_id',
        'product_id',
        'product_type',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CuratedCollection::class, 'collection_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CuratedCollectionSection::class, 'section_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'product_id');
    }

    public function stationery(): BelongsTo
    {
        return $this->belongsTo(Stationery::class, 'product_id');
    }

    public function isStationery(): bool
    {
        return $this->product_type === 'stationery';
    }
}
