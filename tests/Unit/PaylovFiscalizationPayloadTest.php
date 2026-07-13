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
    public function test_register_payload_contains_receipt_and_advance_contract_fields(): void
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
        ]], 1, 'advance-contract-7');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://gw.paylov.test/merchant/fiscalization/register/'
                && $request['transactionId'] === 'transaction-1'
                && $request['receiptType'] === 1
                && $request['advanceContractId'] === 'advance-contract-7'
                && count($request['items']) === 1;
        });
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
}
