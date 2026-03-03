<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClub extends Model
{
    use HasFactory;

    protected $table = 'book_club';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_type',
        'text',
        'is_deleted',
        'repost',
        'reposted_user_id'
    ];
    
    protected $casts = [
    'is_deleted' => 'boolean',
    'repost' => 'boolean'
    ];

    /**
     * Polymorphic relationship - kitob yoki kanselyariya
     */
    public function product()
    {
        return $this->morphTo();
    }

    /**
     * Post egasi
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Post rasmlari
     */
    public function images()
    {
        return $this->hasMany(BookClubImages::class, 'post_id', 'id');
    }

    /**
     * Ovoz berish variantlari
     */
    public function votes()
    {
        return $this->hasMany(BookClubVotes::class, 'post_id', 'id');
    }

    /**
     * Likelar
     */
    public function likes()
    {
        return $this->hasMany(BookClubLikes::class, 'post_id', 'id');
    }

    /**
     * Izohlar
     */
    public function comments()
    {
        return $this->hasMany(BookClubComment::class, 'post_id', 'id');
    }
    public function originalAuthor()
{
    return $this->belongsTo(User::class, 'reposted_user_id');
}
}