<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'external_id',
        'slug',
        'source_url',
    ];

    protected $appends = [
        'image_url',
    ];

    public function books()
    {
        return $this->hasMany(Books::class, 'author_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! is_string($this->image) || trim($this->image) === '') {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}
