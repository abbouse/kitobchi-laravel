<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    protected $fillable = ['title', 'description', 'order'];

    public function items()
    {
        return $this->hasMany(ReelItem::class)->orderBy('order', 'asc');
    }
}