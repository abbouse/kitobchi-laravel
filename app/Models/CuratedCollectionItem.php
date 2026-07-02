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

    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'product_id');
    }
}
