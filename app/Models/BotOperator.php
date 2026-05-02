<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotOperator extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'name',
        'username',
        'is_active',
        'status',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(BotTicket::class, 'operator_id', 'telegram_id');
    }
}
