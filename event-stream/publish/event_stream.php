<?php

use Menumbing\EventStream\Driver\Redis\DefaultRedisId;
use Menumbing\EventStream\Driver\Redis\RedisStream;
use Menumbing\EventStream\Enum\ReadMessageFrom;

use function Hyperf\Support\env;

/**
 * This file contains configuration for the Event Stream system.
 * It defines the consumer group settings and available stream drivers.
 */
return [
    /**
     * The name of the consumer group used for stream processing.
     * This value will be used to identify and group consumers processing the same streams.
     */
    'group_name' => env('APP_NAME', 'menumbing'),

    /**
     * Available stream drivers configuration.
     * Each driver defines how messages are published and consumed.
     * You can configure multiple drivers with different settings.
     */
    'drivers'    => [
        'default' => [
            'driver'  => RedisStream::class,
            'id'      => DefaultRedisId::class,
            'options' => [
                'pool'             => 'default', // Redis connection pool name
                'read_from'        => ReadMessageFrom::GROUP_CREATED, // Starting point for reading messages
                'wait_time'        => 100, // Wait time in milliseconds between read attempts
                'retention_period' => 7, // Message retention period in days
                'approx' => false, // Use approximate for deleting messages
            ],
        ],
    ],

    /**
     * Consumer configuration settings.
     * Controls how stream consumers operate and process messages.
     *
     * Options:
     * - processes: Array of process configurations for different streams.
     *   Each key represents a stream identifier with the value being the number of processes.
     *   Example: ['stream1' => 2] will create 2 processes for 'stream1'
     *
     * - block_for: The number of seconds to sleep between processing batches of messages.
     *   This helps control the consumption rate and system resources.
     */
    'consumer' => [
        'processes' => [],
        'block_for' => 1,
    ],

    /**
     * Serialization configuration for messages.
     * Controls how messages are serialized/deserialized when publishing/consuming.
     *
     * Options:
     * - serializer: The serializer service to use (please refer to serializer configuration)
     * - format: The format to serialize messages to (e.g. json)
     */
    'serialization' => [
        'serializer' => 'default',
        'format' => 'json',
    ]
];
