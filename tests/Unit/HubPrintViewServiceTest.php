<?php

namespace Tests\Unit;

use App\Models\Hub;
use App\Models\OrderFulfillment;
use App\Models\Sold;
use App\Services\HubPrintViewService;
use PHPUnit\Framework\TestCase;

class HubPrintViewServiceTest extends TestCase
{
    public function test_label_qr_uses_existing_label_code(): void
    {
        $label = $this->makeLabelData('LBL-281-120000');

        $this->assertSame('LBL-281-120000', $label['label_code']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $label['qr_data_uri']);
        $this->assertStringContainsString('<svg', base64_decode(substr($label['qr_data_uri'], strpos($label['qr_data_uri'], ',') + 1)));
    }

    public function test_label_qr_falls_back_to_scannable_order_code(): void
    {
        $label = $this->makeLabelData(null);

        $this->assertSame('ORD-281', $label['label_code']);
    }

    private function makeLabelData(?string $labelCode): array
    {
        $order = new Sold([
            'deliveryType' => 'delivery',
            'items' => [],
            'address' => [[
                'fullName' => 'Test User',
                'phoneNumber' => '+998901234567',
                'fullAddress' => 'Toshkent shahri',
            ]],
            'amount' => 125000,
        ]);
        $order->setRelation('user', null);

        $fulfillment = new OrderFulfillment([
            'order_id' => 281,
            'is_cod' => false,
            'label_code' => $labelCode,
        ]);
        $fulfillment->setRelation('order', $order);
        $fulfillment->setRelation('hub', new Hub(['name' => 'Toshkent Markaziy']));

        return (new HubPrintViewService)->labelData($fulfillment);
    }
}
