<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookClubWarning extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'admin_id',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(BookClub::class, 'post_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
