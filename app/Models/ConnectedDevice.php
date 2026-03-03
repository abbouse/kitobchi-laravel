<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectedDevice extends Model
{
    use HasFactory;

    protected $table = 'connected_devices';

    protected $fillable = [
        'token',
        'device_id',
        'device_name',
        'platform',
        'project_id',
        'user_type',
        'user_id',
        'fcm_token'
    ];

    public function courier()
    {
         return $this->belongsTo(Couriers::class, 'user_id');
     }
    public $timestamps = true;
}
