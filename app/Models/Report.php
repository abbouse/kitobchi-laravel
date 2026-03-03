<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'comment',
        'status'
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
}