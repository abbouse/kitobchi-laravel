<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookClubTheme extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'firework', 'status'];
    
    public function posts()
    {
        return $this->hasMany(BookClub::class, 'theme_id', 'id')
            ->where('is_deleted', false);
    }

}