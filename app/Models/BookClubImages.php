<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClubImages extends Model
{
    use HasFactory;
    protected $table = 'book_club_images';
    protected $fillable = [
        'post_id',
        'image',
        'status'
    ];
}
