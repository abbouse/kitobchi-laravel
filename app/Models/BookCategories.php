<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookCategories extends Model
{
    use HasFactory;
    protected $fillable = ['name_ru', 'name_en', 'name_uz', 'name_ja', 'slug', 'is_active'];

    public function tags()
    {
        return $this->belongsToMany(BookTag::class, 'category_tag_relations', 'category_id', 'tag_id');
    }

    public function books()
    {
        return $this->hasMany(Books::class, 'category_id');
    }
}
