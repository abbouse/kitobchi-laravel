<?php

namespace Tests\Unit;

use App\Enums\OrderStatusCode;
use App\Services\PostalTracking\PostalTrackingStatusCatalog;
use PHPUnit\Framework\TestCase;

class PostalTrackingStatusCatalogTest extends TestCase
{
    public function test_it_localizes_known_provider_codes_without_provider_text(): void
    {
        $catalog = new PostalTrackingStatusCatalog;

        $this->assertSame(
            'in_sorting_facility',
            $catalog->normalize('In sorting warehouse'),
        );
        $this->assertSame(
            'Saralash markazida',
            $catalog->labels('in_sorting_facility')['uz'],
        );
        $this->assertSame(
            'В сортировочном центре',
            $catalog->labels('in_sorting_facility')['ru'],
        );
        $this->assertSame('in_transit', $catalog->step('in_sorting_facility'));
    }

    public function test_it_maps_only_customer_milestones_to_order_statuses(): void
    {
        $catalog = new PostalTrackingStatusCatalog;

        $this->assertSame(
            OrderStatusCode::DELIVERED,
            $catalog->orderStatusTarget('ready_for_issue'),
        );
        $this->assertSame(
            OrderStatusCode::CUSTOMER_RECEIVED,
            $catalog->orderStatusTarget('issued_to_recipient'),
        );
        $this->assertNull($catalog->orderStatusTarget('out_for_delivery'));
    }

    public function test_unknown_provider_codes_get_a_safe_localized_label(): void
    {
        $catalog = new PostalTrackingStatusCatalog;

        $this->assertSame(
            'Pochta holati yangilandi',
            $catalog->labels('provider_added_a_new_status')['uz'],
        );
        $this->assertSame('in_transit', $catalog->step('provider_added_a_new_status'));
    }
}
