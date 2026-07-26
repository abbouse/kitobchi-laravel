<?php

namespace Tests\Unit;

use App\Models\OrderFulfillment;
use App\Services\PostalTracking\PostalOrderStatusTransitionService;
use App\Services\PostalTracking\UzPostTrackingProvider;
use App\Services\PostalTrackingService;
use Mockery;
use Tests\TestCase;

class PostalTrackingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_reuses_fresh_cache_for_the_same_provider_and_tracking_number(): void
    {
        config()->set('services.uzpost.sync_interval_minutes', 10);

        $provider = Mockery::mock(UzPostTrackingProvider::class);
        $provider->shouldReceive('code')->once()->andReturn('uzpost');
        $provider->shouldNotReceive('track');
        $transition = Mockery::mock(PostalOrderStatusTransitionService::class);
        $transition->shouldReceive('reconcile')->once()->andReturnFalse();

        $service = new PostalTrackingService($provider, $transition);
        $fulfillment = $this->fulfillment([
            'provider_code' => 'uzpost',
            'provider_name' => 'UzPost',
            'tracking_number' => 'MM204493686UZ',
            'status_code' => 'ready_for_issue',
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        $result = $service->sync($fulfillment);

        $this->assertSame('ready_for_issue', $result['status_code']);
    }

    public function test_it_does_not_reuse_cache_from_another_tracking_number(): void
    {
        config()->set('services.uzpost.sync_interval_minutes', 10);

        $provider = Mockery::mock(UzPostTrackingProvider::class);
        $provider->shouldReceive('code')->twice()->andReturn('uzpost');
        $provider->shouldReceive('name')->once()->andReturn('UzPost');
        $provider->shouldReceive('track')
            ->once()
            ->with('MM204493686UZ')
            ->andReturn([
                'status_code' => 'ready_for_issue',
                'labels' => [
                    'uz' => 'Olib ketishga tayyor',
                    'ru' => 'Готов к выдаче',
                    'en' => 'Ready for issue',
                    'ja' => 'Ready for issue',
                ],
                'status_at' => '2026-07-25T06:45:39+00:00',
                'location' => "UzPost - 125 pochta bo'limi",
                'step' => 'handoff',
                'terminal' => false,
                'recipient_address' => 'TOSHKENT 125-SON AB',
                'recipient_postcode' => '100125',
                'events' => [],
            ]);
        $transition = Mockery::mock(PostalOrderStatusTransitionService::class);
        $transition->shouldReceive('reconcile')->once()->andReturnFalse();

        $service = new PostalTrackingService($provider, $transition);
        $fulfillment = $this->fulfillment([
            'provider_code' => 'uzpost',
            'provider_name' => 'UzPost',
            'tracking_number' => 'MM000000000UZ',
            'status_code' => 'in_transit',
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        $result = $service->sync($fulfillment);

        $this->assertSame('ready_for_issue', $result['status_code']);
        $this->assertSame(
            'MM204493686UZ',
            data_get($fulfillment->meta, 'postal_tracking.tracking_number'),
        );
    }

    public function test_provider_failure_never_reuses_another_trackings_status(): void
    {
        $provider = Mockery::mock(UzPostTrackingProvider::class);
        $provider->shouldReceive('code')->twice()->andReturn('uzpost');
        $provider->shouldReceive('name')->once()->andReturn('UzPost');
        $provider->shouldReceive('track')
            ->once()
            ->with('MM204493686UZ')
            ->andThrow(new \RuntimeException('Provider vaqtincha ishlamayapti.'));
        $transition = Mockery::mock(PostalOrderStatusTransitionService::class);
        $transition->shouldNotReceive('reconcile');

        $service = new PostalTrackingService($provider, $transition);
        $fulfillment = $this->fulfillment([
            'provider_code' => 'uzpost',
            'provider_name' => 'UzPost',
            'tracking_number' => 'MM000000000UZ',
            'status_code' => 'issued_to_recipient',
            'last_attempt_at' => now()->toIso8601String(),
        ]);

        $this->assertNull($service->sync($fulfillment));
        $this->assertNull(data_get($fulfillment->meta, 'postal_tracking.status_code'));
        $this->assertSame(
            'MM204493686UZ',
            data_get($fulfillment->meta, 'postal_tracking.tracking_number'),
        );
    }

    /**
     * @param  array<string, mixed>  $cached
     */
    private function fulfillment(array $cached): OrderFulfillment
    {
        return new class(['order_id' => 281, 'postal_provider' => 'uzpost', 'postal_tracking_number' => 'MM204493686UZ', 'meta' => ['postal_tracking' => $cached]]) extends OrderFulfillment
        {
            public function saveQuietly(array $options = []): bool
            {
                return true;
            }
        };
    }
}
