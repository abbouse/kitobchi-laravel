<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClubLikes extends Model
{
    use HasFactory;

    protected $table = 'book_club_likes';

    protected $fillable = [
        'post_id',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(BookClub::class, 'post_id');
    }
}
