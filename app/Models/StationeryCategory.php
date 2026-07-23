<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationeryCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name_ru', 'name_en', 'name_uz', 'name_ja', 'slug', 'is_active', 'ofd_ikpu_code', 'ofd_package_code'];

    /** Kategoriya o'zgarsa allCategories keshini tozalaymiz. */
    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('api:all_categories:v1');
        static::saved($forget);
        static::deleted($forget);
    }

    // Bog‘langan taglar
    public function tags()
    {
        return $this->belongsToMany(
            StationeryTag::class,
            'stationery_category_tag',
            'category_id',
            'tag_id'
        );
    }

    public function stationeries()
    {
        return $this->hasMany(Stationery::class, 'category_id');
    }
}