<?php

namespace Tests\Unit;

use App\Exceptions\PaylovApiException;
use App\Services\PaylovService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tests\TestCase;

class PaylovFiscalizationPayloadTest extends TestCase
{
    public function test_standard_receipt_does_not_send_advance_contract_id(): void
    {
        Http::fake([
            'https://gw.paylov.test/*' => Http::response([
                'result' => ['ofd' => ['receiptUrl' => 'https://ofd.test/receipt']],
            ]),
        ]);

        $service = new PaylovService(
            baseUrl: 'https://gw.paylov.test',
            accessToken: 'test-access-token',
        );

        $service->registerFiscalReceipt('transaction-1', [[
            'title' => 'Kitob',
            'price' => 100000,
            'count' => 1,
            'code' => '123',
            'vat_percent' => 0,
            'package_code' => '1',
            'tin' => '123456789',
        ]]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://gw.paylov.test/merchant/fiscalization/register/'
                && $request['transactionId'] === 'transaction-1'
                && ! isset($request['receiptType'])
                && ! isset($request['advanceContractId'])
                && count($request['items']) === 1;
        });
    }

    public function test_split_credit_receipt_sends_contract_reference(): void
    {
        Http::fake([
            'https://gw.paylov.test/*' => Http::response([
                'result' => ['ofd' => ['receiptUrl' => 'https://ofd.test/credit-receipt']],
            ]),
        ]);

        $service = new PaylovService(
            baseUrl: 'https://gw.paylov.test',
            accessToken: 'test-access-token',
        );

        $service->registerFiscalReceipt('split-transaction-2', [[
            'title' => 'Kitob',
            'price' => 250000,
            'count' => 1,
            'code' => '123',
            'vat_percent' => 0,
            'package_code' => '1',
            'tin' => '123456789',
        ]], 2, 'N-00000007');

        Http::assertSent(fn (Request $request) => $request['transactionId'] === 'split-transaction-2'
            && $request['receiptType'] === 2
            && $request['advanceContractId'] === 'N-00000007'
        );
    }

    public function test_fiscal_context_keeps_standard_and_split_credit_receipts_separate(): void
    {
        config([
            'services.paylov.ofd.split_credit_receipt_type' => 2,
        ]);

        $service = new \App\Services\PaylovFiscalizationService;
        $method = (new ReflectionClass($service))->getMethod('receiptMetaFor');
        $method->setAccessible(true);

        $standard = $method->invoke($service, new \App\Models\Transaction([
            'payment_type' => 'order',
        ]));
        $split = $method->invoke($service, new \App\Models\Transaction([
            'payment_type' => 'split',
            'provider_response' => [
                'fiscal_contract_id' => 'N-00000007',
            ],
        ]));

        $this->assertSame(['flow' => 'standard', 'receipt_type' => null, 'advance_contract_id' => null], $standard);
        $this->assertSame(['flow' => 'split_credit', 'receipt_type' => 2, 'advance_contract_id' => 'N-00000007'], $split);
    }

    public function test_paylov_error_preserves_required_field_context(): void
    {
        Http::fake([
            'https://gw.paylov.test/*' => Http::response([
                'result' => null,
                'error' => [
                    'code' => 'field_required',
                    'message' => 'field_required',
                    'data' => ['field' => 'advanceContractId'],
                ],
            ], 400),
        ]);

        $service = new PaylovService(
            baseUrl: 'https://gw.paylov.test',
            accessToken: 'test-access-token',
        );

        try {
            $service->registerFiscalReceipt('transaction-1', [], 1);
            $this->fail('PaylovApiException was not thrown.');
        } catch (PaylovApiException $error) {
            $this->assertSame('field_required', $error->apiCode);
            $this->assertSame(400, $error->httpStatus);
            $this->assertSame('advanceContractId', $error->errorData['field']);
            $this->assertFalse($error->isRetryable());
            $this->assertStringContainsString('field: advanceContractId', $error->getMessage());
        }
    }

    public function test_discount_is_distributed_per_unit_without_exceeding_price(): void
    {
        $service = new \App\Services\PaylovFiscalizationService;
        $method = (new ReflectionClass($service))->getMethod('allocateDiscount');
        $method->setAccessible(true);

        [$items, $remaining] = $method->invoke($service, [[
            'title' => 'Kitob',
            'price' => 100,
            'discount' => 0,
            'count' => 3,
        ]], 100);

        $this->assertSame(0, $remaining);
        $this->assertSame(200, array_sum(array_map(
            fn (array $item) => ($item['price'] - $item['discount']) * $item['count'],
            $items,
        )));
        $this->assertCount(2, $items);
        $this->assertTrue(collect($items)->every(fn (array $item) => $item['discount'] < $item['price']));
    }

    public function test_build_fiscal_items_prioritizes_pinfl_over_tin(): void
    {
        config([
            'services.paylov.ofd.pinfl' => '30101901234567',
            'services.paylov.ofd.tin' => '123456789',
            'services.paylov.ofd.book_ikpu' => '05801001001000000',
            'services.paylov.ofd.book_package_code' => '123456',
        ]);

        $order = new \App\Models\Sold([
            'amount' => 50000,
            'items' => [[
                'type' => 'book',
                'name' => 'Test Kitob',
                'item_price' => 50000,
                'count_item' => 1,
            ]],
        ]);

        $service = new \App\Services\PaylovFiscalizationService;
        $items = $service->buildFiscalItems($order);

        $this->assertCount(1, $items);
        $this->assertSame('30101901234567', $items[0]['pinfl']);
        $this->assertArrayNotHasKey('tin', $items[0]);
    }
}
