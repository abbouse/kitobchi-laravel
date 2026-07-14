<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZoneRule extends Model
{
    protected $fillable = [
        'zone_name',
        'country_code',
        'scope',
        'region_name',
        'district_name',
        'city_name',
        'center_lat',
        'center_lon',
        'radius_km',
        'polygon',
        'color',
        'bbox_min_lat',
        'bbox_min_lon',
        'bbox_max_lat',
        'bbox_max_lon',
        'delivery_service_id',
        'priority',
        'base_price',
        'additional_seller_percent',
        'free_price_from',
        'eta_days',
        'cod_allowed',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'center_lat' => 'float',
        'center_lon' => 'float',
        'radius_km' => 'float',
        'polygon' => 'array',
        'bbox_min_lat' => 'float',
        'bbox_min_lon' => 'float',
        'bbox_max_lat' => 'float',
        'bbox_max_lon' => 'float',
        'priority' => 'integer',
        'base_price' => 'integer',
        'additional_seller_percent' => 'float',
        'free_price_from' => 'integer',
        'eta_days' => 'integer',
        'cod_allowed' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function deliveryService(): BelongsTo
    {
        return $this->belongsTo(DeliveryService::class, 'delivery_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Polygon nuqtalarini [[lat, lon], ...] ko'rinishiga tozalab keltiradi.
     * Yaroqsiz yoki 3 tadan kam nuqta bo'lsa null qaytaradi.
     *
     * @return array<int, array{0: float, 1: float}>|null
     */
    public static function sanitizePolygon(mixed $points): ?array
    {
        if (is_string($points)) {
            $points = json_decode($points, true);
        }

        if (! is_array($points)) {
            return null;
        }

        $clean = [];
        foreach ($points as $point) {
            $lat = null;
            $lon = null;

            if (is_array($point)) {
                $lat = $point[0] ?? ($point['lat'] ?? null);
                $lon = $point[1] ?? ($point['lon'] ?? ($point['lng'] ?? null));
            }

            if (! is_numeric($lat) || ! is_numeric($lon)) {
                continue;
            }

            $clean[] = [round((float) $lat, 7), round((float) $lon, 7)];
        }

        return count($clean) >= 3 ? $clean : null;
    }

    /**
     * Polygon uchun bounding box (min/max lat va lon) hisoblaydi.
     *
     * @param  array<int, array{0: float, 1: float}>  $polygon
     * @return array{min_lat: float, min_lon: float, max_lat: float, max_lon: float}
     */
    public static function boundingBox(array $polygon): array
    {
        $lats = array_column($polygon, 0);
        $lons = array_column($polygon, 1);

        return [
            'min_lat' => min($lats),
            'min_lon' => min($lons),
            'max_lat' => max($lats),
            'max_lon' => max($lons),
        ];
    }
}
