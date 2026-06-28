# Outbox Pattern for Hyperf

A transactional outbox pattern implementation for Hyperf applications using the [menumbing/event-stream](../event-stream) component.

## The Problem

When publishing events to a stream (Redis/Kafka) from within a database transaction, you face the **dual-write problem**:

- If the DB commits but the stream publish fails → **event is lost**
- If the stream publish succeeds but the DB rolls back → **phantom event** delivered to consumers

## The Solution

The outbox pattern solves this by writing the event to an `outbox_messages` database table **in the same transaction** as the business data. A background worker process then relays the stored messages to the actual stream driver.

```
Application (within DB transaction)          Worker Process (background)

  Repository::save()                           loop:
    → BEGIN TRANSACTION                          → BEGIN TRANSACTION
    → model.push()                               → SELECT ... FOR UPDATE SKIP LOCKED
    → domain event dispatched                    → for each record:
      → OutboxProduceEventHandler                      → stream->publish(message)
        → OutboxStorage::store()                       → markAsSent() or
    → COMMIT                                         → markAsFailed()
                                               → COMMIT
                                               → sleep(interval)
```

This guarantees:
- **Atomicity** — the outbox record commits or rolls back with the business data
- **At-least-once delivery** — the worker retries until success or dead-letter
- **Decoupling** — the application never blocks on stream availability

## Installation

```bash
composer require menumbing/outbox
```

## Requirements

- PHP >= 8.1
- Hyperf Framework >= 3.1
- menumbing/event-stream >= 1.0
- menumbing/orm >= 1.0

## Configuration

Publish the configuration and migration files:

```bash
php bin/hyperf.php vendor:publish menumbing/outbox
```

This creates:
- `config/autoload/outbox.php` — the outbox configuration
- `migrations/2024_01_01_000000_create_outbox_messages_table.php` — the database migration

Run the migration:

```bash
php bin/hyperf.php migrate
```

### Configuration Options

```php
// config/autoload/outbox.php

return [
    // The produce handler class used by event-stream's RegisterProducers.
    // Set to OutboxProduceEventHandler::class to route event publishing
    // through the outbox. Set to ProduceEventHandler::class to disable.
    'produce_handler' => OutboxProduceEventHandler::class,

    // Outbox storage configuration.
    'storage' => [
        'class'      => DatabaseOutboxStorage::class,
        'connection' => 'default',       // DB connection (must match your ORM connection)
        'table'      => 'outbox_messages',
    ],

    // Worker (relay) process configuration.
    'worker' => [
        'enabled'        => true,       // Set to false to disable the worker
        'nums'           => 1,          // Number of worker processes
        'batch_size'     => 100,       // Messages per poll cycle
        'interval'       => 2,         // Seconds between poll cycles
        'max_retries'    => 5,          // Retries before dead-lettering
        'retry_after'    => 60,         // Seconds before retrying a failed message
    ],

    // Pruning configuration.
    'prune' => [
        'retention_days' => 7,          // Delete sent messages older than N days
    ],
];
```

## Usage

### Producing Events

Producing events works exactly the same as with `menumbing/event-stream`. Annotate your event class with `#[ProducedEvent]` and dispatch it via the event dispatcher:

```php
<?php

namespace App\Event;

use Menumbing\EventStream\Annotation\ProducedEvent;

#[ProducedEvent(stream: 'user-events', name: 'user.created', driver: 'default')]
class UserCreated
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username,
        public readonly string $email
    ) {
    }
}
```

```php
<?php

use App\Event\UserCreated;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserController
{
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function create(): void
    {
        // ... create user logic ...

        // Dispatch event — it will be stored in the outbox table
        // (within the current DB transaction) instead of being
        // published directly to Redis/Kafka.
        $this->eventDispatcher->dispatch(new UserCreated($userId, $username, $email));
    }
}
```

When the outbox is enabled, the event is stored in `outbox_messages` instead of being published directly to the stream. The worker process will relay it to the actual stream driver asynchronously.

### Consuming Events

Consuming events works exactly the same as with `menumbing/event-stream`. Create a consumer event class annotated with `#[ConsumedEvent]` and a listener for it. See the [event-stream documentation](../event-stream/README.md) for details.

## How It Works

### 1. Event Storage (within DB transaction)

When an event annotated with `#[ProducedEvent]` is dispatched, the `OutboxProduceEventHandler` intercepts it. Instead of calling `StreamInterface::publish()`, it calls `OutboxStorageInterface::store()`, which inserts a row into the `outbox_messages` table.

Because the storage uses the same database connection as the ORM, the INSERT participates in the active database transaction (started by the ORM's `EnableDatabaseTransaction` middleware). If the transaction commits, the outbox record commits. If it rolls back, the outbox record is rolled back — no phantom events.

### 2. Event Relay (worker process)

The `OutboxRelayProcess` runs as a Hyperf custom process. Each polling cycle runs within a **storage transaction** to ensure safe concurrent processing:

1. **SELECT ... FOR UPDATE SKIP LOCKED** — locks pending rows, skipping any already locked by other workers. This allows multiple worker processes (`nums > 1`) to run concurrently without processing the same messages.
2. **Publish** — each locked message is published to the actual `StreamInterface` (Redis/Kafka).
3. **Update status** — within the same transaction:
   - **Success** → status set to `sent`, `OutboxRelayed` event dispatched
   - **Failure** → retry count incremented, `OutboxRelayFailed` event dispatched
   - **Max retries exceeded** → status set to `dead_letter`, `OutboxDeadLettered` event dispatched
4. **COMMIT** — releases all row locks.
5. **Sleep** — waits for `interval` seconds before the next cycle, avoiding constant database polling.

Failed messages are retried after the configured `retry_after` delay (in seconds).

> **Note:** `FOR UPDATE SKIP LOCKED` requires MySQL 8.0+ or PostgreSQL 9.5+.

### 3. Message Lifecycle

| Status | Description |
|---|---|
| `pending` | Initial state — waiting to be relayed |
| `failed` | Relay failed at least once — will be retried after delay |
| `sent` | Successfully relayed to the stream |
| `dead_letter` | Exceeded max retries — requires manual intervention |

## Monitoring

The `OutboxMessage` model is provided for querying the outbox table:

```php
use Menumbing\Outbox\Model\OutboxMessage;

// Count pending messages
$count = OutboxMessage::query()
    ->whereIn('status', ['pending', 'failed'])
    ->count();

// Find dead-lettered messages
$deadLettered = OutboxMessage::query()
    ->where('status', 'dead_letter')
    ->get();

// Re-queue a dead-lettered message
$deadLettered->first()->update(['status' => 'pending', 'retry_count' => 0]);
```

## Pruning

Sent messages are retained for auditing but should be pruned periodically to prevent unbounded table growth. The `outbox:prune` command deletes sent messages older than the configured retention period:

```bash
# Use the configured retention_days (default: 7)
php bin/hyperf.php outbox:prune

# Override retention for this run only
php bin/hyperf.php outbox:prune --days=30
```

### Cron Setup

Add to your server's crontab:

```bash
# Prune sent outbox messages daily at 3 AM
0 3 * * * cd /path/to/project && php bin/hyperf.php outbox:prune
```

### Configuration

```php
// config/autoload/outbox.php
'prune' => [
    'retention_days' => 7,  // delete messages older than 7 days
],
```

> **Note:** Only messages with `status = sent` are pruned. Pending, failed, and dead-lettered messages are never affected.

## Events

The worker dispatches the following events. You can create listeners for monitoring, alerting, or custom retry logic:

| Event | When | Payload |
|---|---|---|
| `OutboxRelayed` | Message successfully relayed to stream | `OutboxRecord` |
| `OutboxRelayFailed` | Relay failed but will be retried | `OutboxRecord`, `Throwable` |
| `OutboxDeadLettered` | Message exceeded max retries | `OutboxRecord`, `Throwable` |

```php
<?php

namespace App\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Menumbing\Outbox\Event\OutboxDeadLettered;

#[Listener]
class AlertDeadLetteredMessages implements ListenerInterface
{
    public function listen(): array
    {
        return [OutboxDeadLettered::class];
    }

    public function process(object $event): void
    {
        if ($event instanceof OutboxDeadLettered) {
            // Send alert, log, or trigger manual review
            // $event->record->id
            // $event->record->message->stream
            // $event->throwable->getMessage()
        }
    }
}
```

## Custom Storage Implementation

You can implement a custom storage (e.g., Redis-backed) by implementing `Menumbing\Contract\Outbox\OutboxStorageInterface` and configuring it:

```php
// config/autoload/outbox.php
'storage' => [
    'class' => App\Storage\RedisOutboxStorage::class,
    // ...
],
```

## Backward Compatibility

The outbox package integrates with `menumbing/event-stream` via a small, backward-compatible change to `RegisterProducers`:

- The handler class is resolved via `Container::make()` (instead of `new`) so that subclasses using `#[Inject]` (e.g. `OutboxProduceEventHandler`) are properly wired through the DI container. This has no effect on the default `ProduceEventHandler`, which has no injected dependencies.
- The handler class is resolved from config with fallback: `outbox.produce_handler` → `event_stream.produce_handler` → `ProduceEventHandler::class`.

As a result:

- When the outbox package is **not installed**, event-stream uses the default `ProduceEventHandler` (publishes directly to the stream)
- When installed, `outbox.produce_handler` is set to `OutboxProduceEventHandler::class` by default — events are routed through the outbox
- To disable the outbox and revert to direct publishing:

```php
// config/autoload/outbox.php
'produce_handler' => \Menumbing\EventStream\Handler\ProduceEventHandler::class,
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
