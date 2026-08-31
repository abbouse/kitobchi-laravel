<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ShopApiController;
use App\Models\Books;
use App\Models\MyCart;
use App\Services\CashbackHistoryService;
use App\Services\CourierTaskOrchestratorService;
use App\Services\DeliveryZoneResolverService;
use App\Services\FulfillmentRoutingService;
use App\Services\OrderFinancialSnapshotService;
use App\Services\OrderRealtimeService;
use App\Services\OrderService;
use App\Services\PaylovOrderPaymentService;
use App\Services\PaylovPayablePaymentService;
use App\Services\PostalResendService;
use App\Services\PostalTrackingService;
use App\Services\ProductReviewPromptService;
use App\Services\QrTokenService;
use App\Services\UserReputationService;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CollectionCheckoutLogicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_collection_checkout_rows_keep_original_prices_without_custom_total(): void
    {
        $controller = new ShopApiController(
            Mockery::mock(PaylovPayablePaymentService::class),
            Mockery::mock(DeliveryZoneResolverService::class),
        );

        $method = (new ReflectionClass($controller))->getMethod('buildCollectionCheckoutRows');
        $method->setAccessible(true);

        $rows = $method->invoke($controller, [
            [
                'product_id' => 10,
                'quantity' => 2,
                'price' => 15000,
                'line_total' => 30000,
            ],
            [
                'product_id' => 11,
                'quantity' => 1,
                'price' => 22000,
                'line_total' => 22000,
            ],
        ], null);

        $this->assertSame([
            [
                'product_id' => 10,
                'quantity' => 2,
                'unit_price' => 15000,
            ],
            [
                'product_id' => 11,
                'quantity' => 1,
                'unit_price' => 22000,
            ],
        ], $rows);
    }

    public function test_collection_checkout_rows_proportionally_allocate_custom_total(): void
    {
        $controller = new ShopApiController(
            Mockery::mock(PaylovPayablePaymentService::class),
            Mockery::mock(DeliveryZoneResolverService::class),
        );

        $method = (new ReflectionClass($controller))->getMethod('buildCollectionCheckoutRows');
        $method->setAccessible(true);

        $rows = $method->invoke($controller, [
            [
                'product_id' => 21,
                'quantity' => 1,
                'price' => 10000,
                'line_total' => 10000,
            ],
            [
                'product_id' => 22,
                'quantity' => 1,
                'price' => 20000,
                'line_total' => 20000,
            ],
        ], 25000);

        $this->assertSame(25000, array_sum(array_map(
            fn (array $row) => $row['unit_price'] * $row['quantity'],
            $rows
        )));
        $this->assertSame([
            [
                'product_id' => 21,
                'quantity' => 1,
                'unit_price' => 8333,
            ],
            [
                'product_id' => 22,
                'quantity' => 1,
                'unit_price' => 16667,
            ],
        ], $rows);
    }

    public function test_collection_checkout_uses_price_item_only_for_customer_side_not_seller_side(): void
    {
        $controller = new PurchaseController(
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
            Mockery::mock(PostalTrackingService::class),
            Mockery::mock(\App\Services\OrderStatusPushService::class),
        );

        $book = new Books([
            'price' => 20000,
            'discountPrice' => 18000,
        ]);

        $cart = new MyCart([
            'priceItem' => 12500,
            'product_type' => 'book',
        ]);
        $cart->setRelation('product', $book);

        $reflection = new ReflectionClass($controller);
        $customerMethod = $reflection->getMethod('effectiveCartItemUnitPrice');
        $customerMethod->setAccessible(true);
        $sellerMethod = $reflection->getMethod('sellerFacingCartItemUnitPrice');
        $sellerMethod->setAccessible(true);

        $customerPrice = $customerMethod->invoke($controller, $cart);
        $sellerPrice = $sellerMethod->invoke($controller, $cart);

        $this->assertSame(12500.0, $customerPrice);
        $this->assertSame(18000.0, $sellerPrice);
    }
}
