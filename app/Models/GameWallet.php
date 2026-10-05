<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GameWallet extends Model
{
    protected $table = 'game_wallets';

    protected $guarded = ['id'];

    protected $casts = ['last_daily_at' => 'datetime', 'coins' => 'integer'];
}
