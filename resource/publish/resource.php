<?php

use Menumbing\Resource\Strategy;

return [
    'server_name' => 'Hyperf',

    'default' => Strategy\ChainResourceStrategy::class,

    'strategies' => [
        Strategy\JsonErrorStrategy::class,
        Strategy\JsonResourceStrategy::class,
    ]
];
