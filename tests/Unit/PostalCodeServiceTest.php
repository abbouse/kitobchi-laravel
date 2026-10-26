<?php

namespace Tests\Unit;

use App\Services\PostalCodeService;
use PHPUnit\Framework\TestCase;

/** Pochta indeksi: eng yaqin O'zpochta bo'limi va javobni ajratish. */
class PostalCodeServiceTest extends TestCase
{
    private array $offices = [
        ['index' => '100000', 'lat' => 41.3111, 'lon' => 69.2797], // Toshkent markazi
        ['index' => '100115', 'lat' => 41.2856, 'lon' => 69.2045], // Chilonzor
        ['index' => '140100', 'lat' => 39.6542, 'lon' => 66.9597], // Samarqand
    ];

    public function test_nearest_office_is_chosen(): void
    {
        $this->assertSame('100115', PostalCodeService::nearest($this->offices, 41.2870, 69.2100)['index']);
        $this->assertSame('100000', PostalCodeService::nearest($this->offices, 41.3150, 69.2850)['index']);
        $this->assertSame('140100', PostalCodeService::nearest($this->offices, 39.6600, 66.9500)['index']);
    }

    public function test_too_far_gives_null(): void
    {
        // Orolbo'yi — yaqin atrofda bo'lim yo'q
        $this->assertNull(PostalCodeService::nearest($this->offices, 44.0, 59.0));
    }

    public function test_distance_is_reasonable(): void
    {
        $km = PostalCodeService::distanceKm(41.3111, 69.2797, 39.6542, 66.9597);
        $this->assertTrue($km > 260 && $km < 280, "Toshkent–Samarqand ~270 km, keldi: $km");
    }

    public function test_parse_handles_various_shapes(): void
    {
        $json = ['data' => [
            ['index' => '100000', 'lat' => '41.3111', 'lng' => '69.2797', 'name_uz' => 'Toshkent'],
            ['post_index' => 140100, 'location' => [39.6542, 66.9597]],          // [lat, lon]
            ['code' => '170100', 'coordinates' => [72.344, 40.7821]],            // [lon, lat]
            ['index' => '12345', 'lat' => 41.0, 'lon' => 69.0],                  // 5 xonali — tashlanadi
            ['index' => '100000', 'lat' => '41.3111', 'lng' => '69.2797'],       // takror
            ['index' => '999999', 'lat' => 55.75, 'lon' => 37.61],               // O'zbekistondan tashqari
        ]];
        $parsed = PostalCodeService::parseOffices($json);

        $this->assertCount(3, $parsed);
        $this->assertSame(['100000', '140100', '170100'], array_column($parsed, 'postal_index'));
        $this->assertEqualsWithDelta(40.7821, $parsed[2]['lat'], 0.0001);
        $this->assertEqualsWithDelta(72.344, $parsed[2]['lon'], 0.0001);
        $this->assertSame('Toshkent', $parsed[0]['name']);
    }
}
