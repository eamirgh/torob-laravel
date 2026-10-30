<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Torob Public Key
    |--------------------------------------------------------------------------
    |
    | The base64 or PEM encoded public key provided by Torob to verify incoming
    | JWT requests (Ed25519).
    |
    */
    'public_key' => env('TOROB_PUBLIC_KEY', 'MCowBQYDK2VwAyEAt6Mu4T0pBORY11W+QeM35UsmLO3vsf+6yKpFDEImFk0='),

    /*
    |--------------------------------------------------------------------------
    | Expected Audience
    |--------------------------------------------------------------------------
    |
    | Expected audience claim (aud) in incoming JWT requests. Defaults to host.
    |
    */
    'audience' => env('TOROB_AUDIENCE'),

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled' => env('TOROB_ROUTES_ENABLED', true),
        'prefix' => env('TOROB_ROUTES_PREFIX', ''),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound Webhook & Shop API Tokens
    |--------------------------------------------------------------------------
    */
    'webhook_token' => env('TOROB_WEBHOOK_TOKEN'),
    'shop_token' => env('TOROB_SHOP_TOKEN'),
    'shop_generator_token' => env('TOROB_SHOP_GENERATOR_TOKEN'),
    'shop_domain' => env('TOROB_SHOP_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Tracking Feature Toggles
    |--------------------------------------------------------------------------
    */
    'order_tracking_enabled' => env('TOROB_ORDER_TRACKING_ENABLED', true),
    'action_tracking_enabled' => env('TOROB_ACTION_TRACKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'orders' => env('TOROB_ORDERS_TABLE', 'orders'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Mappings (Optional)
    |--------------------------------------------------------------------------
    |
    | If your Eloquent models implement TorobProductContract, TorobOrderContract,
    | or TorobActionContract, specify their classes here to use the built-in
    | Eloquent providers without writing custom provider code.
    |
    */
    'models' => [
        'product' => env('TOROB_PRODUCT_MODEL'),
        'order' => env('TOROB_ORDER_MODEL'),
        'action' => env('TOROB_ACTION_MODEL'),
    ],
];
