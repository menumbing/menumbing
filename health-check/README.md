# Menumbing Health Check

Cloud native health check component for [Hyperf](https://hyperf.io). Provides Kubernetes-compatible liveness and readiness probe endpoints with built-in checkers for common services.

## Installation

```bash
composer require menumbing/health-check
```

Publish the configuration file:

```bash
php bin/hyperf.php vendor:publish menumbing/health-check
```

This will create `config/autoload/health_check.php`.

## Endpoints

Once installed, two HTTP endpoints are automatically registered:

| Endpoint             | Purpose                                                                 |
|----------------------|-------------------------------------------------------------------------|
| `/health/liveness`   | Returns whether the application is running. A failure triggers a restart. |
| `/health/readiness`  | Returns whether the application is ready to accept traffic.              |

Both endpoints return JSON with HTTP `200` when healthy or `503` when unhealthy.

**Liveness response example:**

```json
{
  "status": "alive",
  "services": {
    "memory_consumption": {
      "name": "memory",
      "status": true,
      "message": "Memory usage is OK"
    }
  }
}
```

**Readiness response example:**

```json
{
  "status": "ready",
  "services": {
    "database": {
      "name": "db",
      "status": true,
      "message": "Database connection is OK"
    },
    "redis": {
      "name": "redis",
      "status": true,
      "message": "Redis connection is OK"
    },
    "amqp": {
      "name": "amqp",
      "status": true,
      "message": "AMQP connection is OK"
    }
  }
}
```

## Built-in Checkers

| Checker          | Name     | Required Package       | Description                                        |
|------------------|----------|------------------------|----------------------------------------------------|
| `DbChecker`     | `db`     | `hyperf/db-connection` | Verifies database connectivity via `SELECT 1`.     |
| `MemoryChecker`  | `memory` | -                      | Checks if memory usage is within the allowed limit. |
| `RedisChecker`   | `redis`  | `hyperf/redis`         | Pings the Redis server to verify connectivity.     |
| `AmqpChecker`    | `amqp`   | `hyperf/amqp`          | Verifies the AMQP (RabbitMQ) connection status.    |

## Configuration

The configuration file (`config/autoload/health_check.php`) has three sections:

### Checks

Define which checks to run for each probe type:

```php
'checks' => [
    'liveness' => [
        'memory_consumption' => [
            'checker' => 'memory',
            'options' => ['max_memory' => 1000], // Max memory in MB
        ],
    ],
    'readiness' => [
        'database' => [
            'checker' => 'db',
            'options' => ['connection' => 'default'],
        ],
        'redis' => [
            'checker' => 'redis',
            'options' => ['pool' => 'default'],
        ],
        'amqp' => [
            'checker' => 'amqp',
            'options' => ['pool' => 'default'],
        ],
    ],
],
```

### Routes

Configure the HTTP server and endpoint paths:

```php
'route' => [
    'server' => 'http',
    'paths' => [
        'liveness'  => '/health/liveness',
        'readiness' => '/health/readiness',
    ],
],
```

Set a path to `null` to disable that endpoint.

### Checkers

Register all available checker classes:

```php
'checkers' => [
    Menumbing\HealthCheck\Checker\DbChecker::class,
    Menumbing\HealthCheck\Checker\MemoryChecker::class,
    Menumbing\HealthCheck\Checker\RedisChecker::class,
    Menumbing\HealthCheck\Checker\AmqpChecker::class,
],
```

Only include checkers for packages that are installed in your project. Remove any checker whose corresponding package is not available.

## Checker Options

### DbChecker

| Option       | Type   | Default     | Description               |
|--------------|--------|-------------|---------------------------|
| `connection` | string | `'default'` | Database connection name. |

### MemoryChecker

| Option       | Type     | Default | Description                                           |
|--------------|----------|---------|-------------------------------------------------------|
| `max_memory` | int/null | `null`  | Maximum memory usage in MB. `null` to skip the check. |

### RedisChecker

| Option | Type   | Default     | Description      |
|--------|--------|-------------|------------------|
| `pool` | string | `'default'` | Redis pool name. |

### AmqpChecker

| Option | Type   | Default     | Description     |
|--------|--------|-------------|-----------------|
| `pool` | string | `'default'` | AMQP pool name. |

## Custom Checker

Create a custom checker by implementing `Menumbing\Contract\HealthCheck\CheckerInterface`:

```php
<?php

declare(strict_types=1);

namespace App\HealthCheck;

use Menumbing\Contract\HealthCheck\CheckerInterface;
use Menumbing\Contract\HealthCheck\ResultInterface;
use Menumbing\HealthCheck\Result;

final class ElasticsearchChecker implements CheckerInterface
{
    const CHECKER_NAME = 'elasticsearch';

    public function getName(): string
    {
        return self::CHECKER_NAME;
    }

    public function check(array $options = []): ResultInterface
    {
        try {
            // Your health check logic here
            return new Result(self::CHECKER_NAME, true, 'Elasticsearch is OK');
        } catch (\Throwable $e) {
            return new Result(self::CHECKER_NAME, false, $e->getMessage());
        }
    }
}
```

Then register it in `config/autoload/health_check.php`:

```php
'checkers' => [
    // ...existing checkers
    App\HealthCheck\ElasticsearchChecker::class,
],

'checks' => [
    'readiness' => [
        // ...existing checks
        'elasticsearch' => [
            'checker' => 'elasticsearch',
            'options' => [],
        ],
    ],
],
```

## License

MIT
