<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
    ];

    protected $appends = [
        'image_url',
    ];

    public function books()
    {
        return $this->hasMany(Books::class, 'publisher_id');
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
