<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Define the health checks for liveness and readiness probes. Each check
    | must reference a registered checker by name and may include options
    | specific to that checker.
    |
    | - "liveness"  checks determine if the application is running properly.
    |               A failed liveness check typically results in a container restart.
    |
    | - "readiness" checks determine if the application is ready to accept traffic.
    |               A failed readiness check removes the instance from the load balancer.
    |
    */
    'checks' => [
        'liveness' => [
            'memory_consumption' => [
                'checker' => 'memory',
                'options' => [
                    // Maximum allowed memory usage in megabytes. Set to null to disable.
                    'max_memory' => 1000,
                ],
            ],
        ],

        'readiness' => [
            'database' => [
                'checker' => 'db',
                'options' => [
                    // The database connection name as defined in config/autoload/databases.php.
                    'connection' => 'default',
                ],
            ],
            'redis' => [
                'checker' => 'redis',
                'options' => [
                    // The Redis pool name as defined in config/autoload/redis.php.
                    'pool' => 'default',
                ],
            ],
            'amqp' => [
                'checker' => 'amqp',
                'options' => [
                    // The AMQP pool name as defined in config/autoload/amqp.php.
                    'pool' => 'default',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP server and paths for the health check endpoints.
    | Routes are automatically registered on application boot.
    |
    | - "server" specifies which Hyperf HTTP server to register routes on.
    | - "paths"  defines the URL paths for liveness and readiness endpoints.
    |            Set a path to null to disable that endpoint.
    |
    */
    'route' => [
        'server' => 'http',
        'paths' => [
            'liveness' => '/health/liveness',
            'readiness' => '/health/readiness',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registered Checkers
    |--------------------------------------------------------------------------
    |
    | List all checker classes that should be available for health checks.
    | Each checker must implement Menumbing\Contract\HealthCheck\CheckerInterface.
    |
    | Note: Only include checkers for packages that are installed. For example,
    | remove RedisChecker if hyperf/redis is not installed, or remove
    | AmqpChecker if hyperf/amqp is not installed.
    |
    */
    'checkers' => [
        Menumbing\HealthCheck\Checker\DbChecker::class,
        Menumbing\HealthCheck\Checker\MemoryChecker::class,
        Menumbing\HealthCheck\Checker\RedisChecker::class,
        Menumbing\HealthCheck\Checker\AmqpChecker::class,
    ],
];
