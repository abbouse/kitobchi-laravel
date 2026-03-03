<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashbackSetting extends Model
{
    protected $fillable = ['fromUzs', 'toUzs', 'cashback'];
    
    public static function getCashbackPercentage(int $amountInUzs): int
    {
        $setting = self::where('fromUzs', '<=', $amountInUzs)
                       ->where('toUzs', '>=', $amountInUzs)
                       ->first();

        return $setting ? (int) $setting->cashback : 0;
    }
}