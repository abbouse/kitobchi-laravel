<?php

namespace App\Enums;

enum FulfillmentMode: string
{
    case HUB_BASED = 'hub_based';
    case DIRECT_COURIER = 'direct_courier';
    case POSTAL_ONLY_VIA_HUB = 'postal_only_via_hub';
    case PICKUP_ONLY = 'pickup_only';
}
