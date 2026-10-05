<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GameFragment extends Model
{
    protected $table = 'game_fragments';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;
}
