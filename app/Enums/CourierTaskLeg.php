<?php

namespace App\Enums;

enum CourierTaskLeg: string
{
    case FIRST_MILE = 'first_mile';
    case LAST_MILE = 'last_mile';
    case POSTAL_HANDOFF = 'postal_handoff';
    case DIRECT_DELIVERY = 'direct_delivery';
}
