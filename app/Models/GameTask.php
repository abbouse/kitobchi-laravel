<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sovg'alar g'ildiragi o'yini. */
class GameTask extends Model
{
    protected $table = 'game_tasks';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'coins' => 'integer'];
}
