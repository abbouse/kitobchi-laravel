<?php

namespace App\Enums;

enum PostalReturnStatus: string
{
    case NONE = 'none';
    case RETURNED_TO_SENDER = 'returned_to_sender';
    case RESEND_PENDING_PAYMENT = 'resend_pending_payment';
    case RESENT = 'resent';
}
