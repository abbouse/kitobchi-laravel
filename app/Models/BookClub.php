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
        'theme_id',
        'repost',
        'reposted_user_id',
        'edited_at',
        'edit_count',
        'last_edited_by_id',
        'ai_post_score',
        'ai_post_checked_at',
        'ai_post_feedback_notified_at',
        'ai_post_status',
        'ai_post_note',
        'ai_post_model',
        'kangaroo_post_star',
        'kangaroo_post_checked_at',
        'kangaroo_post_ugc_status',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'repost' => 'boolean',
        'edited_at' => 'datetime',
        'ai_post_checked_at' => 'datetime',
        'ai_post_feedback_notified_at' => 'datetime',
        'kangaroo_post_checked_at' => 'datetime',
    ];

    // ── Morph map — product_type qiymatlari model klasslarga bog'lanadi ──────
    // Bu bo'lmasa Laravel 'book' → 'App\Models\book' deb qidiradi (xato!)
    // Controller manualdan load qilgani uchun bu relation
    // to'g'ridan chaqirilmaydi, lekin ehtiyot uchun to'g'ri belgilaymiz.
    public function product()
    {
        return $this->morphTo(__FUNCTION__, 'product_type', 'product_id');
    }

    // ── User ──────────────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ── Original author (repost uchun) ────────────────────────────────────────
    public function originalAuthor()
    {
        return $this->belongsTo(User::class, 'reposted_user_id', 'id');
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by_id', 'id');
    }

    // ── Theme ─────────────────────────────────────────────────────────────────
    public function theme()
    {
        return $this->belongsTo(BookClubTheme::class, 'theme_id', 'id');
    }

    // ── Rasmlar ───────────────────────────────────────────────────────────────
    public function images()
    {
        return $this->hasMany(BookClubImages::class, 'post_id', 'id');
    }

    // ── Ovoz variantlari ──────────────────────────────────────────────────────
    public function votes()
    {
        return $this->hasMany(BookClubVotes::class, 'post_id', 'id');
    }

    // ── Likelar ───────────────────────────────────────────────────────────────
    public function likes()
    {
        return $this->hasMany(BookClubLikes::class, 'post_id', 'id');
    }

    // ── Izohlar ───────────────────────────────────────────────────────────────
    public function comments()
    {
        return $this->hasMany(BookClubComment::class, 'post_id', 'id');
    }

    public function warnings()
    {
        return $this->hasMany(BookClubWarning::class, 'post_id', 'id');
    }

    public function activeWarning()
    {
        return $this->hasOne(BookClubWarning::class, 'post_id', 'id')
            ->select([
                'book_club_warnings.id',
                'book_club_warnings.post_id',
                'book_club_warnings.user_id',
                'book_club_warnings.admin_id',
                'book_club_warnings.note',
                'book_club_warnings.is_active',
                'book_club_warnings.created_at',
            ])
            ->where('is_active', true)
            ->latestOfMany();
    }
}
