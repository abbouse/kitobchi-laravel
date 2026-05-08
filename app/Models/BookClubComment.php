<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClubComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'parent_id',
        'kangaroo_star_equivalent',
        'kangaroo_toxicity',
        'kangaroo_checked_at',
        'kangaroo_ugc_status',
    ];

    protected $casts = [
        'kangaroo_checked_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(BookClub::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(BookClubCommentLike::class, 'comment_id');
    }

    public function replies()
    {
        return $this->hasMany(BookClubComment::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(BookClubComment::class, 'parent_id');
    }
}
