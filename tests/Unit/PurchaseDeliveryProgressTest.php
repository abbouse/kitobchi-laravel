<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\PurchaseController;
use App\Models\OrderFulfillment;
use App\Models\Sold;
use App\Services\CashbackHistoryService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\DeliveryZoneResolverService;
use App\Services\FulfillmentRoutingService;
use App\Services\OrderFinancialSnapshotService;
use App\Services\OrderRealtimeService;
use App\Services\OrderService;
use App\Services\PaylovOrderPaymentService;
use App\Services\PostalResendService;
use App\Services\PostalTrackingService;
use App\Services\ProductReviewPromptService;
use App\Services\QrTokenService;
use App\Services\UserReputationService;
use Illuminate\Support\Carbon;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class PurchaseDeliveryProgressTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_uzpost_history_is_added_as_localized_child_events(): void
    {
        $postalTracking = Mockery::mock(PostalTrackingService::class);
        $controller = $this->controller($postalTracking);

        $fulfillment = new OrderFulfillment([
            'status_code' => 'dispatched_to_post',
            'postal_provider' => 'uzpost',
            'postal_tracking_number' => 'MM204493686UZ',
            'dispatched_to_post_at' => Carbon::parse('2026-07-25T10:00:00+05:00'),
        ]);
        $fulfillment->setRelation('hub', null);

        $order = new Sold;
        $order->forceFill([
            'id' => 281,
            'deliveryType' => 'postal',
            'status_code' => 'in_delivery',
            'payment_status_code' => 'paid',
            'created_at' => Carbon::parse('2026-07-24T10:00:00+05:00'),
        ]);
        $order->setRelation('fulfillment', $fulfillment);

        $postalTracking->shouldReceive('sync')
            ->once()
            ->with($fulfillment)
            ->andReturn([
                'provider_code' => 'uzpost',
                'provider_name' => 'UzPost',
                'tracking_number' => 'MM204493686UZ',
                'status_code' => 'ready_for_issue',
                'labels' => [
                    'uz' => 'Olib ketishga tayyor',
                    'ru' => 'Готов к выдаче',
                    'en' => 'Ready for issue',
                    'ja' => 'Ready for issue',
                ],
                'status_at' => '2026-07-25T06:00:00+00:00',
                'location' => "UzPost - 125 pochta bo'limi",
                'step' => 'handoff',
                'terminal' => false,
                'events' => [
                    [
                        'status_code' => 'ready_for_issue',
                        'labels' => [
                            'uz' => 'Pochta bo‘limiga yetib bordi, olib ketishga tayyor',
                            'ru' => 'Прибыло в почтовое отделение и готово к выдаче',
                            'en' => 'Arrived at the post office and ready for pickup',
                            'ja' => '郵便局に到着し、受け取り可能です',
                        ],
                        'status_at' => '2026-07-25T06:00:00+00:00',
                        'location' => "UzPost - 125 pochta bo'limi",
                        'step' => 'handoff',
                        'terminal' => false,
                    ],
                    [
                        'status_code' => 'in_sorting_facility',
                        'labels' => [
                            'uz' => 'Saralash markazida',
                            'ru' => 'В сортировочном центре',
                            'en' => 'At the sorting facility',
                            'ja' => '仕分けセンターに到着しました',
                        ],
                        'status_at' => '2026-07-25T05:58:00+00:00',
                        'location' => "UzPost - 125 pochta bo'limi",
                        'step' => 'in_transit',
                        'terminal' => false,
                    ],
                    [
                        'status_code' => 'in_transit',
                        'labels' => [
                            'uz' => 'Manzil tomon yo‘lda',
                            'ru' => 'В пути к месту назначения',
                            'en' => 'On the way to the destination',
                            'ja' => 'お届け先へ輸送中です',
                        ],
                        'status_at' => '2026-07-24T12:29:34+00:00',
                        'location' => null,
                        'step' => 'in_transit',
                        'terminal' => false,
                    ],
                ],
            ]);

        $method = (new ReflectionClass($controller))->getMethod('buildDeliveryProgress');
        $method->setAccessible(true);
        $progress = $method->invoke($controller, $order);

        $this->assertSame('postal', $progress['delivery_flow']);
        $this->assertSame('handoff', $progress['active_step']);
        $this->assertSame('ready_for_issue', $progress['postal_tracking']['status_code']);

        $external = collect($progress['timeline'])
            ->where('is_external', true)
            ->values();
        $this->assertCount(3, $external);
        $this->assertSame('postal_provider_in_transit', $external[0]['code']);
        $this->assertSame('Manzil tomon yo‘lda', $external[0]['title_uz']);
        $this->assertSame('postal_provider_in_sorting_facility', $external[1]['code']);
        $this->assertSame('Saralash markazida', $external[1]['title_uz']);
        $this->assertSame('postal_provider_ready_for_issue', $external[2]['code']);
        $this->assertSame('handoff', $external[2]['step']);
        $this->assertSame(
            'Pochta bo‘limiga yetib bordi, olib ketishga tayyor',
            $external[2]['title_uz'],
        );
        $this->assertSame('UzPost', $external[2]['provider_name']);
        $this->assertSame("UzPost - 125 pochta bo'limi", $external[2]['location']);
        $this->assertSame($external[2], collect($progress['timeline'])->last());
    }

    private function controller(PostalTrackingService $postalTracking): PurchaseController
    {
        return new PurchaseController(
            Mockery::mock(OrderService::class),
            Mockery::mock(CashbackHistoryService::class),
            Mockery::mock(OrderRealtimeService::class),
            Mockery::mock(PostalResendService::class),
            Mockery::mock(QrTokenService::class),
            Mockery::mock(DeliveryZoneResolverService::class),
            Mockery::mock(FulfillmentRoutingService::class),
            Mockery::mock(CourierTaskOrchestratorService::class),
            Mockery::mock(UserReputationService::class),
            Mockery::mock(PaylovOrderPaymentService::class),
            Mockery::mock(ProductReviewPromptService::class),
            Mockery::mock(OrderFinancialSnapshotService::class),
            $postalTracking,
        );
    }
}
