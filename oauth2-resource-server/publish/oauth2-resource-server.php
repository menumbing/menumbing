<?php

use Menumbing\OAuth2\Resource\Provider;
use function Hyperf\Support\env;

return [
    // Public key path or content for token verification
    'public_key'               => env('OAUTH2_PUBLIC_KEY'),

    // Menumbing http client used to communicate with oauth server
    'oauth_server_http_client' => 'oauth2',

    'client' => [
        // Configure default user provider and its available provider(s)
        'provider'  => 'stateless',
        'providers' => [
            'stateless' => Provider\Client\StatelessClientProvider::class,
        ],
    ],

    'access_token' => [
        // Configure default client provider and its available provider(s)
        'provider'  => 'stateless',
        'providers' => [
            'stateless' => Provider\AccessToken\StatelessAccessTokenProvider::class,
            'api'       => Provider\AccessToken\ApiAccessTokenProvider::class,
        ],
    ],
];