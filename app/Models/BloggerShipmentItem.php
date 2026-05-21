<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloggerShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'blogger_shipment_id',
        'name',
        'position',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(BloggerShipment::class, 'blogger_shipment_id');
    }
}
