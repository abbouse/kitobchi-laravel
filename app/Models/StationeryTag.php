<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationeryTag extends Model
{
    use HasFactory;

    protected $fillable = ['name_ru', 'name_en', 'name_uz', 'name_ja', 'slug'];

    public function categories()
    {
        return $this->belongsToMany(
            StationeryCategory::class,
            'stationery_category_tag',
            'tag_id',
            'category_id'
        );
    }
}