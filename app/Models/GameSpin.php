<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GameSpin extends Model
{
    protected $table = 'game_spins';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    protected $casts = ['payload' => 'array'];
}
