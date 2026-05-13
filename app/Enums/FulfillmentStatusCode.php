<?php

namespace App\Enums;

enum FulfillmentStatusCode: string
{
    case AWAITING_SELLER_PREP = 'awaiting_seller_prep';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case PICKED_FROM_SELLER = 'picked_from_seller';
    case ARRIVED_AT_HUB = 'arrived_at_hub';
    case QC_CHECKED = 'qc_checked';
    case PACKED = 'packed';
    case LABELED = 'labeled';
    case DISPATCHED_TO_POST = 'dispatched_to_post';
    case ASSIGNED_LAST_MILE = 'assigned_last_mile';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';
}
