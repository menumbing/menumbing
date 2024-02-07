<?php

use Menumbing\Resource\Strategy;

return [
    'default' => Strategy\ChainResourceStrategy::class,

    'strategies' => [
        Strategy\JsonErrorStrategy::class,
        Strategy\JsonResourceStrategy::class,
    ]
];
