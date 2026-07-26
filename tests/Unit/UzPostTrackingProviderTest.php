<?php

namespace Tests\Unit;

use App\Services\PostalTracking\UzPostTrackingProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UzPostTrackingProviderTest extends TestCase
{
    public function test_it_normalizes_latest_uzpost_status_for_mobile_timeline(): void
    {
        config()->set('services.uzpost.tracking_url', 'https://tracking.pochta.uz/api/v1/public/test');

        Http::fake([
            'tracking.pochta.uz/*' => Http::response([
                'header' => [
                    'data' => [
                        'status' => 'ready_for_issue',
                        'locations' => [
                            ['address' => 'SEBZOR 2', 'postcode' => '100019'],
                            ['address' => 'TOSHKENT 125-SON AB', 'postcode' => '100125'],
                        ],
                    ],
                ],
                'shipox' => [
                    'data' => [
                        'list' => [
                            [
                                'status' => 'ready_for_issue',
                                'date' => '2026-07-25T06:45:39.508Z',
                                'status_uz' => 'Olib ketishga tayyor',
                                'status_ru' => 'Готов к выдаче',
                                'status_eng' => 'Ready for issue',
                                'warehouse' => [
                                    'name' => 'Ташкент - 100125',
                                    'public_name' => "UzPost - 125 pochta bo'limi",
                                ],
                            ],
                            [
                                'status' => 'in_transit',
                                'date' => '2026-07-24T12:29:34.175Z',
                                'status_uz' => 'Yo‘lda',
                                'status_ru' => 'В пути',
                                'status_eng' => 'In transit',
                            ],
                        ],
                    ],
                ],
                'gdeposilka' => null,
            ]),
        ]);

        $result = app(UzPostTrackingProvider::class)->track('mm204493686uz');

        $this->assertSame('ready_for_issue', $result['status_code']);
        $this->assertSame(
            'Pochta bo‘limiga yetib bordi, olib ketishga tayyor',
            $result['labels']['uz'],
        );
        $this->assertSame(
            'Прибыло в почтовое отделение и готово к выдаче',
            $result['labels']['ru'],
        );
        $this->assertSame(
            'Arrived at the post office and ready for pickup',
            $result['labels']['en'],
        );
        $this->assertSame('handoff', $result['step']);
        $this->assertSame("UzPost - 125 pochta bo'limi", $result['location']);
        $this->assertSame('TOSHKENT 125-SON AB', $result['recipient_address']);
        $this->assertSame('100125', $result['recipient_postcode']);
        $this->assertFalse($result['terminal']);
        $this->assertCount(2, $result['events']);
        $this->assertSame('ready_for_issue', $result['events'][0]['status_code']);
        $this->assertSame('in_transit', $result['events'][1]['status_code']);
        $this->assertSame('Manzil tomon yo‘lda', $result['events'][1]['labels']['uz']);

        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && $request->url() === 'https://tracking.pochta.uz/api/v1/public/test/MM204493686UZ/');
    }

    public function test_issued_to_recipient_is_terminal_but_stays_under_postal_handoff(): void
    {
        config()->set('services.uzpost.tracking_url', 'https://tracking.pochta.uz/api/v1/public/test');

        Http::fake([
            'tracking.pochta.uz/*' => Http::response([
                'header' => ['data' => ['status' => 'issued_to_recipient']],
                'shipox' => [
                    'data' => [
                        'list' => [[
                            'status' => 'issued_to_recipient',
                            'date' => '2026-07-16T08:47:50.545Z',
                            'status_uz' => 'Qabul qiluvchiga berildi',
                            'status_ru' => 'Выдан получателю',
                            'status_eng' => 'Issued to recipient',
                        ]],
                    ],
                ],
            ]),
        ]);

        $result = app(UzPostTrackingProvider::class)->track('MM196558286UZ');

        $this->assertSame('handoff', $result['step']);
        $this->assertTrue($result['terminal']);
        $this->assertSame('Qabul qiluvchiga topshirildi', $result['labels']['uz']);
        $this->assertSame('issued_to_recipient', $result['events'][0]['status_code']);
    }

    public function test_it_compares_event_dates_by_real_timestamp_across_timezones(): void
    {
        config()->set('services.uzpost.tracking_url', 'https://tracking.pochta.uz/api/v1/public/test');

        Http::fake([
            'tracking.pochta.uz/*' => Http::response([
                'header' => ['data' => ['status' => 'ready_for_issue']],
                'shipox' => [
                    'data' => [
                        'list' => [
                            [
                                'status' => 'in_transit',
                                'date' => '2026-07-25T10:00:00+05:00',
                                'status_uz' => 'Yo‘lda',
                            ],
                            [
                                'status' => 'ready_for_issue',
                                'date' => '2026-07-25T06:00:00+00:00',
                                'status_uz' => 'Olib ketishga tayyor',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $result = app(UzPostTrackingProvider::class)->track('MM204493686UZ');

        $this->assertSame('ready_for_issue', $result['status_code']);
        $this->assertSame('handoff', $result['step']);
    }

    public function test_null_uzpost_payload_is_treated_as_not_found(): void
    {
        config()->set('services.uzpost.tracking_url', 'https://tracking.pochta.uz/api/v1/public/test');

        Http::fake([
            'tracking.pochta.uz/*' => Http::response([
                'header' => null,
                'shipox' => null,
                'gdeposilka' => null,
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UzPost jo‘natmasi topilmadi.');

        app(UzPostTrackingProvider::class)->track('INVALID123456UZ');
    }

    public function test_invalid_tracking_format_is_rejected_before_http_request(): void
    {
        Http::fake();

        try {
            app(UzPostTrackingProvider::class)->track('123');
            $this->fail('Invalid UzPost tracking number was accepted.');
        } catch (\RuntimeException $error) {
            $this->assertSame('UzPost trek raqami formati noto‘g‘ri.', $error->getMessage());
        }

        Http::assertNothingSent();
    }
}
