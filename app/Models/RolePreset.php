<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RolePreset extends Model
{
    protected $fillable = [
        'emoji',
        'title_uz',
        'title_ru',
        'title_en',
        'title_ja',
        'needs_place',
        'sort'
    ];
}