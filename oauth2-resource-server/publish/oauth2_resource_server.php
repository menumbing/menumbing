<?php

use Menumbing\OAuth2\ResourceServer\Provider;
use function Hyperf\Support\env;

return [
    // Public key path or content for token verification
    'public_key' => env('OAUTH2_PUBLIC_KEY'),

    // Menumbing http client used to communicate with oauth server
    'oauth_server_http_client' => 'oauth2',

    'client' => [
        // List client provider(s)
        'providers' => [
            'stateless' => [
                'driver' => Provider\Client\StatelessClientProvider::class,
            ],

            'api' => [
                'driver' => Provider\Client\ApiClientProvider::class,
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
        'repository_provider' => 'stateless',

        // List access token provider(s)
        'providers' => [
            'stateless' => [
                'driver' => Provider\AccessToken\StatelessAccessTokenProvider::class,
            ],

            'api' => [
                'driver' => Provider\AccessToken\ApiAccessTokenProvider::class,
            ],
        ],
    ],
];