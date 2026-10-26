<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** O'zpochta bo'limi: indeks va koordinata (postal:sync-uzpost). */
class PostOffice extends Model
{
    protected $table = 'post_offices';

    protected $fillable = ['postal_index', 'lat', 'lon', 'name', 'address'];

    protected $casts = ['lat' => 'float', 'lon' => 'float'];
}
