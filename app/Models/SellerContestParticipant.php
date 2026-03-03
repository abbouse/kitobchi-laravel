<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerContestParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_contest_id',
        'participant_id',
    ];

    public function contest()
    {
        return $this->belongsTo(SellerContest::class, 'seller_contest_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'participant_id', 'id');
    }
}
