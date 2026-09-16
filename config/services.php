<?php

return [
    'maps' => [
        'provider' => env('MAPS_PROVIDER'),
        'api_key' => env('MAPS_API_KEY'),
    ],
    'routing' => [
        'provider' => env('ROUTING_PROVIDER'),
        'api_key' => env('ROUTING_API_KEY'),
    ],
    'geocoding' => [
        'provider' => env('GEOCODING_PROVIDER'),
        'api_key' => env('GEOCODING_API_KEY'),
    ],
    'threecx' => [
        'base_url' => env('THREECX_BASE_URL'),
        'client_id' => env('THREECX_CLIENT_ID'),
        'client_secret' => env('THREECX_CLIENT_SECRET'),
    ],
];
