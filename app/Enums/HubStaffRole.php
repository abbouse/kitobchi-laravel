<?php

namespace App\Enums;

enum HubStaffRole: string
{
    case MANAGER = 'manager';
    case SUPERVISOR = 'supervisor';
    case INBOUND_OPERATOR = 'inbound_operator';
    case QC_OPERATOR = 'qc_operator';
    case PACKING_OPERATOR = 'packing_operator';
    case DISPATCH_OPERATOR = 'dispatch_operator';
    case INVENTORY_OPERATOR = 'inventory_operator';
    case SUPPORT_OPERATOR = 'support_operator';
}
