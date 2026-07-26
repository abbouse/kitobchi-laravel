<?php

namespace Tests\Unit;

use App\Models\OrderFulfillment;
use App\Models\Sold;
use App\Services\AdminOrderStatusSyncService;
use App\Services\PostalTracking\PostalOrderStatusTransitionService;
use App\Services\PostalTracking\PostalTrackingStatusCatalog;
use Mockery;
use Tests\TestCase;

class PostalOrderStatusTransitionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_recipient_received_advances_order_only_once(): void
    {
        $order = $this->postalOrder('delivered', 'C');
        $fulfillment = $this->fulfillment($order);
        $statusSync = Mockery::mock(AdminOrderStatusSyncService::class);
        $statusSync->shouldReceive('updateMainOrder')
            ->once()
            ->with(
                $order,
                'customer_received',
                Mockery::on(fn (array $options): bool => $options['forward_only'] === true),
            )
            ->andReturnUsing(function (Sold $updatedOrder): void {
                $updatedOrder->forceFill([
                    'status_code' => 'customer_received',
                    'status' => 'D',
                ]);
            });

        $service = new PostalOrderStatusTransitionService(
            new PostalTrackingStatusCatalog,
            $statusSync,
        );
        $tracking = [
            'status_code' => 'issued_to_recipient',
            'provider_code' => 'uzpost',
            'tracking_number' => 'MM196558286UZ',
        ];

        $this->assertTrue($service->reconcile($fulfillment, $tracking));
        $this->assertFalse($service->reconcile($fulfillment, $tracking));
    }

    public function test_ready_for_issue_does_not_regress_a_received_order(): void
    {
        $order = $this->postalOrder('customer_received', 'D');
        $fulfillment = $this->fulfillment($order);
        $statusSync = Mockery::mock(AdminOrderStatusSyncService::class);
        $statusSync->shouldNotReceive('updateMainOrder');

        $service = new PostalOrderStatusTransitionService(
            new PostalTrackingStatusCatalog,
            $statusSync,
        );

        $this->assertFalse($service->reconcile($fulfillment, [
            'status_code' => 'ready_for_issue',
        ]));
    }

    private function postalOrder(string $statusCode, string $legacyStatus): Sold
    {
        $order = new Sold;
        $order->forceFill([
            'id' => 281,
            'deliveryType' => 'postal',
            'status_code' => $statusCode,
            'status' => $legacyStatus,
        ]);

        return $order;
    }

    private function fulfillment(Sold $order): OrderFulfillment
    {
        $fulfillment = new OrderFulfillment([
            'order_id' => $order->id,
            'postal_provider' => 'uzpost',
            'postal_tracking_number' => 'MM196558286UZ',
        ]);
        $fulfillment->forceFill(['id' => 44]);
        $fulfillment->setRelation('order', $order);

        return $fulfillment;
    }
}
