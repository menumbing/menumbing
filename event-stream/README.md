# Event Stream for Hyperf

A powerful event streaming component for Hyperf applications, providing a simple way to produce and consume events using stream engine like Redis and Kafka.

## Installation

You can install the package via composer:

```bash
composer require menumbing/event-stream
```

## Requirements

- PHP >= 8.1
- Hyperf Framework >= 3.1
- Redis Server

## Configuration

Publish the configuration file:

```bash
php bin/hyperf.php vendor:publish menumbing/event-stream
```

This will create a `event_stream.php` configuration file in your `config/autoload` directory.

### Configuration Options

The configuration file contains the following options:

```php
return [
    // The name of the consumer group used for stream processing
    'group_name' => env('APP_NAME', 'menumbing'),

    // Available stream drivers configuration
    'drivers' => [
        'default' => [
            'driver'  => RedisStream::class,
            'id'      => DefaultRedisId::class,
            'options' => [
                'pool'             => 'default', // Redis connection pool name
                'read_from'        => ReadMessageFrom::GROUP_CREATED, // Starting point for reading messages
                'wait_time'        => 100, // Wait time in milliseconds between read attempts
                'retention_period' => 7, // Message retention period in days
                'approx'           => false, // Use approximate for deleting messages
            ],
        ],
    ],

    // Consumer configuration settings
    'consumer' => [
        'processes' => [], // Array of process configurations for different streams
        'block_for' => 1, // The number of seconds to sleep between processing batches
        'retry_after' => 60, // The number of seconds to retry zombie message due to offline consumer or failed process
    ],

    // Serialization configuration for messages
    'serialization' => [
        'serializer' => 'default', // The serializer service to use
        'format'     => 'json', // The format to serialize messages to
    ]
];
```

## Usage

### Producing Events

To produce events, you need to:

1. Create an event class
2. Annotate it with `#[ProducedEvent]`
3. Dispatch the event using the event dispatcher

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
When the event dispatched with EventDispatcher it will be produced to event stream with name `stream-1`, event name `user.created` and driver `default`.
Here is how you dispatch the event:

```php
<?php

namespace App\Controller;

use App\Event\UserCreated;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserController
{
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function create()
    {
        // Create user logic...
        $userId = 123;
        $username = 'john_doe';
        $email = 'john@example.com';

        // Dispatch event
        $this->eventDispatcher->dispatch(new UserCreated($userId, $username, $email));

        // ...
    }
}
```

### Consuming Events

To consume events, you need to:

1. Create an event class on other service
2. Annotate it with `#[ConsumedEvent]`
3. Configure it in the `event_stream.php` configuration file

Example consumer process:

```php
<?php

namespace App\Event;

use Menumbing\EventStream\Annotation\ConsumedEvent;

#[ConsumedEvent(stream: 'user-events', name: 'user.created', driver: 'default', retries: 0)]
class ConsumeUserCreated
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username,
        public readonly string $email
    ) {
    }
}
```
It will automatically create a consumer process for each stream. The option `retries` indicate how many attempt should consumer process the message until it fire `ConsumeFailed` event. Alternatively, you can configure consumers in the `event_stream.php` configuration file:

```php
'consumer' => [
    'processes' => [
        'user-events' => 2, // This will create 2 processes for 'user-events' stream
    ],
    'block_for' => 1, // The number of seconds to sleep between processing batches of messages.
    'retry_after' => 30, // The number of seconds to retry zombie message due to offline consumer or failed process.
],
```

### Handling Consumed Events

To handle consumed events, create an event listeners for your event classes:

```php
<?php

namespace App\Listener;

use App\Event\ConsumeUserCreated;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
class UserCreatedListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            ConsumeUserCreated::class,
        ];
    }

    public function process(object $event): void
    {
        // Handle the UserCreated event
        if ($event instanceof ConsumeUserCreated) {
            // Process the event
            echo "User created: {$event->username} ({$event->email})\n";
        }
    }
}
```

## Debugging

The package includes a debug listener that logs various events when debugging is enabled. To enable debugging, set `app_debug` to `true` in your configuration.

## Advanced Usage

### Custom ID Providers

You can create custom ID providers by implementing the `IdProviderInterface`:

```php
<?php

namespace App\EventStream;

use Menumbing\Contract\EventStream\IdProviderInterface;

class CustomIdProvider implements IdProviderInterface
{
    public function newId(): string
    {
        // Generate and return a custom ID
        return uniqid('custom-', true);
    }
}
```

Then configure it in your `event_stream.php`:

```php
'drivers' => [
    'custom' => [
        'driver'  => RedisStream::class,
        'id'      => CustomIdProvider::class,
        'options' => [
            // ...
        ],
    ],
],
```

### Custom Stream Drivers

You can create custom stream drivers by implementing the `StreamInterface`:

```php
<?php

namespace App\EventStream;

use Generator;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;

class CustomStreamDriver implements StreamInterface
{
    public function createGroup(string $name, string $stream): bool
    {
        // Implementation
    }

    public function publish(StreamMessage $message): string
    {
        // Implementation
    }

    public function subscribe(string $consumer, string $group, array $streams): Generator
    {
        // Implementation
    }

    public function ack(string $group, string $stream, array $ids): bool
    {
        // Implementation
    }
}
```

Then configure it in your `event_stream.php`:

```php
'drivers' => [
    'custom' => [
        'driver'  => CustomStreamDriver::class,
        'options' => [
            // Custom options
        ],
    ],
],
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
