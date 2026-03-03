<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'user' => [
        'driver' => 'sanctum',
        'provider' => 'users',
        ],
        'seller' => [
        'driver' => 'sanctum',
        'provider' => 'sellers',
        ],
        'courier' => [
        'driver' => 'sanctum',
        'provider' => 'couriers',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'sellers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Seller::class,
        ],
        'couriers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Couriers::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'sellers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Seller::class,
        ],
        'couriers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Couriers::class,
        ],
    ],
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
