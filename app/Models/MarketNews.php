<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketNews extends Model
{
    protected $table = 'market_news';
    use HasFactory;
    protected $fillable = [
        'imgUrl',
        'title',
        'description',
        'align',
        'status',
    ];
    protected $casts = [
    'status' => 'boolean'
    ];
    
}
