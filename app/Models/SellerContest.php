<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SellerContest extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'description',
        'end_date',
        'status',
        'winner_count',
        'winners',
    ];

    protected $casts = [
        'winners' => 'array',
        'created_at' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function participants()
    {
        return $this->hasMany(SellerContestParticipant::class, 'seller_contest_id', 'id');
    }

    public function winners()
    {
        return $this->winners ?? [];
    }
    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : Carbon::now();
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }
}
