<?php

use Menumbing\OAuth2\ResourceServer\Driver;
use function Hyperf\Support\env;

return [
    // Public key path or content for token verification
    'public_key' => env('OAUTH2_PUBLIC_KEY'),

    // Location where token might be placed in request if set by OAuth2 Server
    'cookie'     => [
        'name' => 'oauth2_token',
    ],

    // Token Validator Drivers
    'token_validators'  => [
        'stateless' => [
            'driver' => Driver\TokenValidator\StatelessTokenValidator::class,
        ],

        'api' => [
            'driver'  => Driver\TokenValidator\ApiTokenValidator::class,
            'options' => [
                'http_client' => 'oauth2',
            ],
        ],
    ],
];