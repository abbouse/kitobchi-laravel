<?php

return [
    'token'     => env('TELEGRAM_TOKEN'),
    'admins'    => array_map('intval', array_filter(explode(',', env('BOT_ADMINS', '')))),
    'operators' => [],
    'routes'    => true,
    'safe_mode' => false,
    'config'    => [],
];
