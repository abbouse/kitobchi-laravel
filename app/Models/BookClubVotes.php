<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClubVotes extends Model
{
    use HasFactory;
    protected $table = 'book_club_votes';
    protected $fillable = [
        'post_id',
        'user_id',
        'option_text'
    ];
}
