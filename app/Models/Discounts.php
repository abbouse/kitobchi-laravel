<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discounts extends Model
{
    use HasFactory;

    protected $table = 'discounts';
    protected $fillable = [
        'name',
        'discount',
        'created_at'
    ];
    
    public function books()
{
    return $this->belongsToMany(Books::class, 'book_discount', 'discount_id', 'book_id');
}
}
