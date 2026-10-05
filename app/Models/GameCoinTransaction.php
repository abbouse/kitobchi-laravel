<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GameCoinTransaction extends Model
{
    protected $table = 'game_coin_transactions';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;
}
