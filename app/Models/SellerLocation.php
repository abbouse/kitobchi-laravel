<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'lat',
        'lon',
        'fullAddress',
        'description',
        'qr_token',
        'qr_rotated_at',
        'is_main',
        'is_deleted',
        'store_courier_delivery_price',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_deleted' => 'boolean',
        'qr_rotated_at' => 'datetime',
        'store_courier_delivery_price' => 'integer',
    ];

    protected $appends = [
        'qr_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (SellerLocation $location) {
            if (empty($location->qr_token)) {
                $location->qr_token = self::generateUniqueQrToken();
                $location->qr_rotated_at = now();
            }
        });
    }

    public static function generateUniqueQrToken(): string
    {
        do {
            $token = Str::random(40);
            $exists = DB::table('seller_locations')->where('qr_token', $token)->exists();
        } while ($exists);

        return $token;
    }

    public function rotateQrToken(): string
    {
        $this->qr_token = self::generateUniqueQrToken();
        $this->qr_rotated_at = now();
        $this->save();

        return $this->qr_token;
    }

    public function qrUrl(): ?string
    {
        if (empty($this->qr_token)) {
            return null;
        }

        return "kitobchi://s/{$this->qr_token}";
    }

    public function getQrUrlAttribute(): ?string
    {
        return $this->qrUrl();
    }

    public function workdays()
    {
        return $this->hasMany(SellerLocationWorkday::class, 'location_id', 'id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function staff()
    {
        return $this->hasMany(Seller::class, 'seller_location_id')
            ->where('is_hidden', false);
    }

    public function getIsOpenNowAttribute()
    {
        $now = Carbon::now();
        $dayOfWeek = strtolower($now->format('l'));
        $workday = $this->workdays()
            ->where('day_of_week', $dayOfWeek)
            ->first();
        if (! $workday) {
            return false;
        }
        $open = Carbon::parse($workday->open_time);
        $close = Carbon::parse($workday->close_time);

        return $now->between($open, $close);
    }
}
