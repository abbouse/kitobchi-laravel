<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookTag extends Model
{
    protected $fillable = ['tag_name_uz', 'tag_name_ru', 'tag_name_en'];

    // Kitoblar bilan munosabat (ko'p-ko'p)
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_tag_relations', 'tag_id', 'book_id');
    }

    // Kategoriyalar bilan munosabat (ko'p-ko'p)
    public function categories()
    {
        return $this->belongsToMany(BookCategories::class, 'category_tag_relations', 'tag_id', 'category_id');
    }
}