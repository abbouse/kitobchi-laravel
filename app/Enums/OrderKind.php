<?php

namespace App\Enums;

enum OrderKind: string
{
    case STANDARD = 'standard';
    case POSTAL_RESEND = 'postal_resend';
}
