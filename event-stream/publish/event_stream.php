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
     * Debug mode configuration.
     * When enabled, it provides additional logging and information for debugging purposes.
     */
    'debug' => true,

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

                /**
                 * Kafka-specific options (ignored by Redis driver):
                 *
                 * - min_bytes: Minimum bytes the broker should return for a fetch request.
                 *   When set, the broker waits until enough data has accumulated before responding.
                 *   This improves throughput by reducing the number of small fetch round-trips.
                 *   Recommended: 512 or higher for high-throughput topics.
                 *   Default (Kafka): 1 byte (respond immediately with any available data).
                 *
                 * - max_wait: Maximum time (in milliseconds) the broker will wait for a fetch
                 *   request to accumulate enough data before responding. This is the broker-side
                 *   equivalent of `wait_time` and caps how long fetchMessages() can block.
                 *   Must be lower than `wait_time` to avoid consumer stalls — if max_wait is
                 *   greater than wait_time, the consumer loop may block on fetchMessages() longer
                 *   than its intended budget, causing other consumers to appear stuck.
                 *   Recommended: 200–400ms when wait_time is 500–1000ms.
                 *   Default (Kafka): 500ms.
                 *
                 * Example:
                 *   'min_bytes' => 512,
                 *   'max_wait'  => 300,
                 */
            ],
        ],
    ],

    /**
     * Consumer configuration settings.
     * Controls how stream consumers operate and process messages.
     *
     * Options:
     * - processes: Array of process configurations for different streams.
     * Each key represents a stream identifier with the value being the number of processes.
     * Example: ['default:stream1' => 2] will create 2 processes for 'stream1'
     *
     * - block_for: The number of seconds to sleep between processing batches of messages.
     * This helps control the consumption rate and system resources.
     *
     * - retry_after: Time in seconds after which pending messages are considered
     *               for reprocessing if not acknowledged
     */
    'consumer' => [
        'processes' => [
            // Key format: 'driver:stream-name' => <number of processes>
            // Example: 'default:loan-events' => 2
        ],
        'block_for' => 1,
        'retry_after' => 60,
        'concurrent' => [
            // Key format: 'driver:stream-name' => <number of concurrent coroutines>
            // Default: 1 (sequential). Only increase for non-financial/non-ordered streams.
            // Example: 'default:notification-events' => 3
        ],
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
    ],

    /**
     * Exceptions to skip (acknowledge) during consumer processing.
     *
     * When an exception occurs while handling a message, the consumer will normally leave the message unacknowledged
     * so it can be retried later. Add exception classes to this list to *acknowledge the message anyway* when those
     * exceptions are thrown—effectively skipping retries for known non-recoverable/expected failures (e.g. validation
     * errors, permanently missing resources).
     *
     * Notes:
     * - Use this to prevent “poison messages” from being retried indefinitely.
     * - Only include exceptions that are safe to drop; skipped exceptions result in message loss for that failure case.
     *
     * Example:
     *   'skip_exceptions' => [
     *       \DomainException::class,
     *       \InvalidArgumentException::class,
     *   ],
     */
    'skip_exceptions' => [
        \LogicException::class,
        \InvalidArgumentException::class,
        \DomainException::class,
    ],
];
