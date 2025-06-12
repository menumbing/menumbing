<?php

use Menumbing\OAuth2\ResourceServer\Provider;
use function Hyperf\Support\env;

return [
    // Public key path or content for token verification
    'public_key' => env('OAUTH2_PUBLIC_KEY'),

    // Location where token might be placed in request if set by OAuth2 Server
    'cookie' => [
        'name' => 'oauth2_token',
    ],

    'client' => [
        // List client provider(s)
        'providers' => [
            'stateless' => [
                'driver' => Provider\Client\StatelessClientProvider::class,
            ],

            'api' => [
                'driver' => Provider\Client\ApiClientProvider::class,
                'options' => [
                    'http_client' => 'oauth2',
                ],
            ],

            'database' => [
                'driver' => Provider\Client\DatabaseClientProvider::class,
                'options' => [
                    'connection' => 'oauth2',
                ],
            ],
        ],
    ],

    'access_token' => [
        // Configure League OAuth2 Access Token Repository Provider
        'default' => 'stateless',

        // List access token provider(s)
        'providers' => [
            'stateless' => [
                'driver' => Provider\AccessToken\StatelessAccessTokenProvider::class,
            ],

            'api' => [
                'driver' => Provider\AccessToken\ApiAccessTokenProvider::class,
                'options' => [
                    'http_client' => 'oauth2',
                ],
            ],
        ],
    ],
];