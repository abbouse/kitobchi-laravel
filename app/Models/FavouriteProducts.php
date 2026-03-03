<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavouriteProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'product_type'
    ];

    /**
     * Favourite productning kitob bilan bog'lanishi.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Books::class, 'product_id');
    }
}