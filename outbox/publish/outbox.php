<?php

use Menumbing\Outbox\Handler\OutboxProduceEventHandler;
use Menumbing\Outbox\Storage\DatabaseOutboxStorage;

/**
 * Configuration for the transactional outbox pattern.
 *
 * When enabled, events annotated with #[ProducedEvent] are stored in an
 * outbox database table (within the active DB transaction) instead of
 * being published directly to the stream. A background worker process
 * then relays the stored messages to the actual stream driver.
 *
 * This guarantees atomicity: if the DB transaction rolls back, no event
 * is published; if it commits, the event will eventually be delivered.
 */
return [

    /**
     * The produce handler class used by event-stream's RegisterProducers.
     *
     * This value is read by Menumbing\EventStream\Listener\RegisterProducers
     * (which checks outbox.produce_handler first, then falls back to
     * event_stream.produce_handler, then to the default ProduceEventHandler).
     *
     * Set to OutboxProduceEventHandler::class to route event publishing
     * through the outbox. Remove or set to ProduceEventHandler::class to
     * disable the outbox and publish directly to the stream.
     */
    'produce_handler' => OutboxProduceEventHandler::class,

    /**
     * Outbox storage configuration.
     *
     * The storage is responsible for persisting and retrieving outbox
     * messages. The default implementation uses the database connection.
     */
    'storage' => [
        'class'      => DatabaseOutboxStorage::class,
        'connection' => 'default',
        'table'      => 'outbox_messages',
    ],

    /**
     * Worker (relay) process configuration.
     *
     * The worker polls the outbox storage for pending messages and
     * relays them to the actual event stream driver.
     */
    'worker' => [
        'enabled'        => true,
        'nums'           => 1,
        'batch_size'     => 100,
        'interval'       => 2,
        'max_retries'    => 5,
        'retry_after'    => 60,
    ],

    /**
     * Pruning configuration.
     *
     * Sent messages are retained for auditing but should be pruned
     * periodically to prevent unbounded table growth. Run the
     * `outbox:prune` command via cron to clean up old messages.
     *
     *   0 3 * * * php bin/hyperf.php outbox:prune
     */
    'prune' => [
        'retention_days' => 7,
    ],
];
