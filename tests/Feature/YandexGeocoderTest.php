<?php

namespace Tests\Feature;

use App\Services\YandexGeocoderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexGeocoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'cache.default' => 'array',
            'services.yandex_geocoder.keys' => ['key-one', 'key-two', 'key-three'],
        ]);
        Cache::flush();
    }

    public function test_falls_back_to_next_key_and_remembers_the_working_one(): void
    {
        $used = [];
        Http::fake(function (Request $request) use (&$used) {
            $key = $request->data()['apikey'] ?? null;
            $used[] = $key;

            return $key === 'key-one'
                ? Http::response(['statusCode' => 403, 'error' => 'Forbidden', 'message' => 'Invalid key'], 403)
                : Http::response($this->yandexPayload(), 200);
        });

        $service = app(YandexGeocoderService::class);
        $result = $service->reverse(41.311081, 69.240562, 'uz');

        $this->assertSame(['key-one', 'key-two'], $used);
        $this->assertSame("O'zbekiston, Toshkent, Amir Temur ko'chasi, 1", $result['formatted']);
        $this->assertSame('UZ', $result['country_code']);
        $this->assertSame('Toshkent', $result['administrative_area']);
        $this->assertSame('Yunusobod tumani', $result['dependent_locality']);
        $this->assertSame('Toshkent', $result['province']);
        $this->assertEqualsWithDelta(41.311081, $result['lat'], 0.000001);

        // Boshqa nuqta: o'lik kalit chetda, ishlagan kalit birinchi
        $used = [];
        $service->reverse(41.2, 69.2, 'uz');
        $this->assertSame(['key-two'], $used);

        // Xuddi shu nuqta — keshdan, Yandex'ga so'rov yo'q
        $used = [];
        $service->reverse(41.311081, 69.240562, 'uz');
        $this->assertSame([], $used);
    }

    public function test_returns_null_when_every_key_fails_and_does_not_cache_failure(): void
    {
        $down = true;
        Http::fake(function () use (&$down) {
            return $down ? Http::response('', 500) : Http::response($this->yandexPayload(), 200);
        });

        $this->assertNull(app(YandexGeocoderService::class)->reverse(41.3, 69.2, 'ru'));
        Http::assertSentCount(3);

        $down = false;
        Cache::flush(); // chetga qo'yilgan kalitlar muddati tugadi deb faraz qilamiz
        $this->assertNotNull(app(YandexGeocoderService::class)->reverse(41.3, 69.2, 'ru'));
    }

    public function test_search_returns_list_with_coordinates(): void
    {
        Http::fake(fn () => Http::response($this->yandexPayload(), 200));

        $items = app(YandexGeocoderService::class)->search('Amir Temur 1', 'uz');

        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(69.240562, $items[0]['lon'], 0.000001);
    }

    public function test_endpoint_requires_login(): void
    {
        $this->getJson('/api/v1/kitobchi/geocode/reverse?lat=41.3&lon=69.2')->assertUnauthorized();
        $this->getJson('/api/v1/seller/geocode/reverse?lat=41.3&lon=69.2')->assertUnauthorized();
    }

    private function yandexPayload(): array
    {
        return ['response' => ['GeoObjectCollection' => ['featureMember' => [[
            'GeoObject' => [
                'name' => "Amir Temur ko'chasi, 1",
                'description' => "Toshkent, O'zbekiston",
                'Point' => ['pos' => '69.240562 41.311081'],
                'metaDataProperty' => ['GeocoderMetaData' => [
                    'kind' => 'house',
                    'precision' => 'exact',
                    'text' => "O'zbekiston, Toshkent, Amir Temur ko'chasi, 1",
                    'Address' => [
                        'country_code' => 'UZ',
                        'formatted' => "O'zbekiston, Toshkent, Amir Temur ko'chasi, 1",
                        'Components' => [
                            ['kind' => 'country', 'name' => "O'zbekiston"],
                            ['kind' => 'province', 'name' => 'Toshkent'],
                            ['kind' => 'locality', 'name' => 'Toshkent'],
                            ['kind' => 'district', 'name' => 'Yunusobod tumani'],
                            ['kind' => 'street', 'name' => "Amir Temur ko'chasi"],
                            ['kind' => 'house', 'name' => '1'],
                        ],
                    ],
                    'AddressDetails' => ['Country' => [
                        'CountryNameCode' => 'UZ',
                        'CountryName' => "O'zbekiston",
                        'AdministrativeArea' => [
                            'AdministrativeAreaName' => 'Toshkent',
                            'Locality' => [
                                'LocalityName' => 'Toshkent',
                                'DependentLocality' => ['DependentLocalityName' => 'Yunusobod tumani'],
                            ],
                        ],
                    ]],
                ]],
            ],
        ]]]]];
    }
}
