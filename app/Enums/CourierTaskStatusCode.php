<?php

namespace App\Enums;

enum CourierTaskStatusCode: string
{
    case ASSIGNED = 'assigned';
    case ACCEPTED = 'accepted';
    case ARRIVED_AT_PICKUP = 'arrived_at_pickup';
    case PICKED_UP = 'picked_up';
    case DROPPED_OFF = 'dropped_off';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
