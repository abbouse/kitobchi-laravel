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
        'ai_score',
        'ai_checked_at',
        'ai_status',
        'ai_note',
        'ai_model',
        'is_hidden_by_ai',
        'ai_moderation_status',
        'ai_moderated_at',
        'ai_moderation_note',
        'ai_moderation_model',
        'kangaroo_star_equivalent',
        'kangaroo_toxicity',
        'kangaroo_checked_at',
        'kangaroo_ugc_status',
    ];

    protected $casts = [
        'ai_checked_at' => 'datetime',
        'ai_moderated_at' => 'datetime',
        'kangaroo_checked_at' => 'datetime',
        'is_hidden_by_ai' => 'boolean',
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
