# Graceful Process for Hyperf

Process-completion-aware graceful shutdown for [Hyperf](https://hyperf.io) applications running on Swoole.

Instead of relying on fixed timeouts, this package monitors actual process and request completion. The server exits as soon as all in-flight work is done — custom processes finish their current job, and HTTP workers finish serving active requests.

## Features

- **Non-invasive** — works with existing custom processes without code changes; blocking operations (sleep, I/O, message consumption) complete naturally
- **HTTP request protection** — in-flight requests complete normally; new requests are rejected during shutdown
- **Process-aware shutdown** — tracks custom processes and HTTP workers via shared-memory counters; exits immediately when all are done
- **Dual signal support** — handles both SIGTERM (Docker/Kubernetes) and SIGINT (Ctrl+C)
- **Dual mode support** — works with both SWOOLE_BASE and SWOOLE_PROCESS modes
- **Zero configuration** — works out of the box with sensible defaults

## Requirements

- PHP >= 8.1
- Hyperf >= 3.1
- Swoole (SWOOLE_BASE or SWOOLE_PROCESS mode)
- ext-ffi (recommended, for clean process exit and inherited socket cleanup)

## Installation

```bash
composer require menumbing/graceful-process
```

Publish the configuration file:

```bash
php bin/hyperf.php vendor:publish menumbing/graceful-process
```

## Configuration

```php
// config/autoload/graceful_process.php

return [
    /*
     * Safety-net timeout in seconds for the overall shutdown.
     *
     * This should be set to at least the longest expected blocking operation
     * duration in your custom processes.
     *
     * Docker's stop_grace_period (or Kubernetes terminationGracePeriodSeconds)
     * must be >= this value.
     *
     * Default: 300 seconds (5 minutes)
     */
    'timeout' => (int) env('GRACEFUL_PROCESS_TIMEOUT', 300),

    /*
     * Maximum time in seconds that HTTP workers wait for in-flight
     * requests to complete before force-stopping.
     *
     * Set this to at least your longest expected HTTP request duration.
     *
     * Defaults to 'timeout' if not set.
     */
    'max_wait_time' => (int) env('GRACEFUL_PROCESS_MAX_WAIT_TIME', 30),
];
```

### Docker / Kubernetes

Make sure the container's grace period is at least as long as `timeout`:

```yaml
# docker-compose.yml
services:
  app:
    stop_grace_period: 5m   # must be >= graceful_process.timeout
    init: true               # recommended: uses tini for proper signal forwarding
```

```yaml
# Kubernetes
spec:
  terminationGracePeriodSeconds: 300  # must be >= graceful_process.timeout
```

## Usage

### HTTP Requests

HTTP requests are protected automatically — no code changes needed.

When SIGTERM or SIGINT arrives:

1. In-flight requests continue and complete normally
2. New requests are rejected:
   - **SWOOLE_BASE**: listening sockets closed — `connection refused` at TCP level
   - **SWOOLE_PROCESS**: `503 Service Unavailable` with `Retry-After: 5` header
3. Workers exit as soon as all active requests finish

### Custom Processes

Custom processes work out of the box **without any code changes**. When shutdown is triggered:

1. `ProcessManager::isRunning()` returns `false` on the next loop check
2. The current blocking operation (sleep, I/O, message consumption) finishes naturally
3. The process exits cleanly

```php
<?php

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Process\ProcessManager;

#[Process(name: 'consumer')]
class ConsumerProcess extends AbstractProcess
{
    public function handle(): void
    {
        while (ProcessManager::isRunning()) {
            $this->processMessage();
        }
    }

    private function processMessage(): void
    {
        // Your message processing logic (e.g., consume from queue).
        // This can include blocking operations like sleep(), HTTP calls,
        // database queries, etc. The current call will ALWAYS complete
        // before the process exits — even during shutdown.
    }
}
```

### Optional: GracefulShutdown Trait

For additional control, you can use the `GracefulShutdown` trait with `runGracefully()`. This registers a completion channel that blocks until the callback finishes:

```php
<?php

namespace App\Process;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Process\ProcessManager;
use Menumbing\GracefulProcess\Trait\GracefulShutdown;

#[Process(name: 'consumer')]
class ConsumerProcess extends AbstractProcess
{
    use GracefulShutdown;

    public function handle(): void
    {
        $this->runGracefully(function () {
            while (ProcessManager::isRunning()) {
                $this->processMessage();
            }
        });
    }

    private function processMessage(): void
    {
        // The current iteration will always complete before exit.
    }
}
```

## License

MIT
