<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Http Client Options
    |--------------------------------------------------------------------------
    |
    | All http client has these default options. These default options could be
    | replaced for each http client.
    |
    */
    'defaults'        => [
        'timeout' => 60,
        'headers' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Http Client Handler
    |--------------------------------------------------------------------------
    |
    | The default handler for all http clients.
    |
    */
    'handler_factory' => [
        \Menumbing\HttpClient\Factory\GuzzleHttpClientHandlerFactory::class,
        [
            'min_connections' => 1,
            'max_connections' => 30,
            'wait_timeout'    => 3.0,
            'max_idle_time'   => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Http Client Middlewares
    |--------------------------------------------------------------------------
    |
    | Extend http client with middlewares. This is default middleware and will
    | be applied to all http clients. You could set middlewares for specific
    | http client in http_clients config. All middlewares should implement
    | interface "Hyperf\Guzzle\MiddlewareInterface".
    |
    */
    'middlewares' => [
        'retry' => [\Hyperf\Guzzle\RetryMiddleware::class, ['retries' => 1, 'delay' => 10]],
    ],

    /*
    |--------------------------------------------------------------------------
    | Config Namespaces
    |--------------------------------------------------------------------------
    |
    | All registered namespace configs that registered and have http_clients
    | config, will be registered as http clients. It's use same config as
    | http_clients. All http clients that registered in other config will be
    | prefixed with config namespace.
    |
    */
    'config_namespaces' => [],

    /*
    |--------------------------------------------------------------------------
    | Http Clients
    |--------------------------------------------------------------------------
    |
    | Register http clients. Examples:
    |
    |   'github.com' => [
    |       'base_uri' => 'https://github.com/api',
    |       'timeout' => 60,
    |       'headers' => [
    |           'Accept' => 'application/json',
    |       ],
    |       'middlewares' => [
    |           'retry' => [Hyperf\Guzzle\RetryMiddleware::class, ['retries' => 2, 'delay' => 10]],
    |       ]
    |   ]
    |
    */
    'http_clients' => [],
];
