<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    protected $fillable = ['buseiness_version_ios', 'buseiness_version_android', 'market_version_ios', 'market_version_android', 'courier_version_ios', 'courier_version_android'];
}