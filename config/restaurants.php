<?php
return [
    'default_provider' => env('RESTAURANT_PROVIDER', 'google'),
    'providers' => [
        'google' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
            'timeout' => 10,
        ],
        'osm' => [
            'overpass_url' => 'https://overpass-api.de/api/interpreter',
            'timeout' => 15,
        ],
    ],
];
