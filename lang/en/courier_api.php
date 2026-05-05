<?php

return [
    // ── Auth ────────────────────────────────────────────────
    'login_success'         => 'Successfully logged in.',
    'invalid_credentials'   => 'Phone number or password is incorrect.',
    'account_inactive'      => 'Your account is not active. Please contact the administrator.',
    'account_blocked'       => 'Your account has been blocked. Please contact the administrator.',
    'logout_success'        => 'You have logged out.',
    'phone_not_found'       => 'Phone number not found.',
    'phone_not_uz'          => 'Only Uzbekistan numbers (+998) are accepted.',
    'phone_invalid'         => 'Phone number format is invalid.',
    'reset_too_many'        => 'Password reset limit reached. Please try again later.',
    'reset_sms_sent'        => 'A new password has been sent via SMS.',
    'reset_sms_failed'      => 'Failed to reset the password. Please try again later.',
    'request_already_sent'  => 'You are already registered or an active courier.',
    'request_updated'       => 'Application details updated.',
    'request_submitted'     => 'Application submitted successfully. An admin will review it shortly.',

    // ── Orders ──────────────────────────────────────────────
    'order_not_found'       => 'Order not found.',
    'order_already_taken'   => 'This order has already been taken by another courier.',
    'order_confirmed'       => 'Order accepted successfully.',
    'order_delivered'       => 'Order delivered.',
    'order_invalid_qr'      => 'QR code is invalid or expired.',

    // ── Warnings & Block ────────────────────────────────────
    'warning_title'         => 'You have received a warning',
    'warning_count'         => 'After 3 warnings, your account will be blocked.',
    'blocked_by_warnings'   => 'You have received 3 warnings and your account is now blocked.',
    'unblocked'             => 'Your account has been unblocked.',

    // ── Misc ────────────────────────────────────────────────
    'unauthorized'          => 'Authorization required.',
    'fetch_failed'          => 'Failed to fetch data.',
    'validation_failed'     => 'The submitted data contains errors.',
    'server_error'          => 'Server error. Please try again later.',

    // ── Bonus system (Phase 3) ─────────────────────────────
    'bonus_threshold_push_title' => '💰 High-bonus order available!',
    'bonus_threshold_push_body'  => 'The bonus for this order has reached :amount UZS. Be the first to accept it!',
    'sla_warning_push_title'     => '⏰ 5 minutes left to deliver',
    'sla_warning_push_body'      => 'Order #:id deadline is approaching. Late delivery will reduce your bonus.',
    'customer_delay_marked'      => 'Customer is unreachable — timer paused.',
    'customer_delay_resumed'     => 'Pause cleared, timer resumed.',
    'customer_delay_invalid'     => 'Pause is not available for this order.',
    'customer_delay_limit_reached' => 'Pause limit reached. This order can no longer be paused.',
];
