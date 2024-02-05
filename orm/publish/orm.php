<?php

use Menumbing\Orm;

use function Hyperf\Support\env;

return [
    'persistent' => [
        'class'       => Orm\Persistent\DatabasePersistent::class,
        'middlewares' => [
            Orm\Persistent\Middleware\EnableDatabaseTransaction::class,
            Orm\Persistent\Middleware\DispatchEvent::class,
            Orm\Persistent\Middleware\ReleasePendingDomainEvent::class,
        ],
    ],

    'cache' => [
        'enabled' => env('ORM_CACHE_ENABLED', false),
        'store'   => env('ORM_CACHE_STORE', 'default'),
        'ttl'     => env('ORM_CACHE_TTL', 60 * 60 * 24),
    ],
];
