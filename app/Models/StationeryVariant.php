<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationeryVariant extends Model
{
    use HasFactory;

    protected $table = 'stationery_variants';

    protected $fillable = [
        'product_id',
        'color_name',
        'image_path',
        'stock'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Asosiy mahsulotga bog'lanish
    public function product()
    {
        return $this->belongsTo(Stationery::class, 'product_id');
    }

    // Rasm to'liq URL ni qaytarish (agar storage da saqlansa)
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }
}