<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelItem extends Model
{
    protected $fillable = ['reel_id', 'video_720p', 'video_480p', 'video_360p', 'order'];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }
}