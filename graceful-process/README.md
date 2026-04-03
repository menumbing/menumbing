# Graceful Process for Hyperf

Process-completion-aware graceful shutdown for [Hyperf](https://hyperf.io) applications running on Swoole (SWOOLE_BASE mode).

Instead of relying on fixed timeouts, this package monitors actual process and request completion. The server exits as soon as all in-flight work is done — custom processes finish their current job, and HTTP workers finish serving active requests.

## Features

- **Process-aware shutdown** — tracks custom processes and HTTP workers via shared-memory counters; exits immediately when all are done
- **HTTP request protection** — in-flight requests complete normally; new requests receive `503 Service Unavailable` during shutdown
- **Custom process support** — wrap your process loop with `runGracefully()` to ensure the current unit of work finishes before exit
- **Early signal handling** — catches SIGTERM and SIGINT arriving during application bootstrap (before Swoole's handlers are registered)
- **SIGINT support** — Ctrl+C and `kill -INT` trigger the same graceful shutdown as SIGTERM
- **Zero configuration** — works out of the box with sensible defaults

## Requirements

- PHP >= 8.1
- Hyperf >= 3.1
- Swoole (SWOOLE_BASE mode)

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
     * Swoole's C-level max_wait_time is set to this value. If any process
     * is stuck, Swoole force-kills it after this duration.
     *
     * In normal operation, the package sends SIGINT well before this
     * timeout expires, so the actual shutdown is much faster.
     *
     * Docker's stop_grace_period (or Kubernetes terminationGracePeriodSeconds)
     * must be >= this value.
     *
     * Default: 300 seconds (5 minutes)
     */
    'timeout' => (int) \Hyperf\Support\env('GRACEFUL_PROCESS_TIMEOUT', 300),

    /*
     * Maximum time in seconds that HTTP workers wait for in-flight
     * requests to complete before force-stopping.
     *
     * After SIGTERM/SIGINT, each worker immediately stops accepting new requests
     * (responds with 503) and monitors active connections. The worker
     * exits as soon as all in-flight requests finish. This value is only
     * a safety cap — if a request is stuck or takes too long, the worker
     * will be force-stopped after this duration.
     *
     * Set this to at least your longest expected HTTP request duration.
     *
     * Defaults to 'timeout' if not set.
     */
    // 'max_wait_time' => 60,
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

### Custom Processes

Use the `GracefulShutdown` trait and wrap your work loop with `runGracefully()`:

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
        // Your message processing logic.
        // The current iteration will always complete before
        // the process exits — even during shutdown.
    }
}
```

When SIGTERM or SIGINT arrives:

1. `ProcessManager::isRunning()` returns `false` on the next check
2. The current `processMessage()` call finishes completely
3. The process exits cleanly

### HTTP Requests

HTTP requests are protected automatically — no code changes needed.

When SIGTERM or SIGINT arrives:

1. In-flight requests continue and complete normally
2. New requests receive `503 Service Unavailable` with a `Retry-After: 5` header
3. Workers exit as soon as all active requests finish

## How It Works

### Shutdown Flow (SWOOLE_BASE mode)

```
SIGTERM or SIGINT arrives
  |
  +-- (SIGINT at master level)
  |     -> Swoole\Process::signal converts to $server->shutdown()
  |     -> Triggers same SIGTERM flow below
  |
  +-- Workers (GracefulWorkerStopHandler):
  |   1. Set shared shutdown flag (Swoole\Atomic)
  |   2. Register in process counter
  |   3. GracefulShutdownMiddleware starts returning 503
  |   4. Poll connection_num until all requests finish
  |   5. $server->stop() -> worker exits
  |   6. Shutdown function: unregister from counter
  |
  +-- Custom Processes (ShutdownWatcherListener):
  |   1. Detect shutdown flag via timer poll
  |   2. Set ProcessManager::setRunning(false)
  |   3. Close recv() socket to interrupt blocked I/O
  |   4. Wait for runGracefully() callback to complete
  |   5. Process exits naturally
  |   6. Shutdown function: unregister from counter
  |
  +-- When counter reaches 0:
      -> SIGINT sent to master process
      -> Interrupts Swoole's C-level hard sleep
      -> Container exits immediately
```

### Signal Handling

| Signal | Source | Behavior |
|--------|--------|----------|
| SIGTERM | `docker stop` / `kill` | Graceful shutdown |
| SIGINT (1st) | Ctrl+C / `kill -INT` | Converted to `$server->shutdown()` at master level |
| SIGINT (2nd) | Double Ctrl+C | Workers force-stop immediately |
| SIGINT (internal) | Process counter = 0 | Master exits immediately |

### Key Mechanisms

- **Swoole\Atomic (shared memory)** — cross-process shutdown flag and process counter, inherited by all children via `fork()`
- **Process counter** — tracks alive workers + custom processes; SIGINT fires only when ALL reach zero
- **Socket close** — interrupts `AbstractProcess::listen()` blocked `recv()` call, eliminating the ~5-10s poll gap
- **Connection polling** — workers monitor `connection_num` and exit as soon as in-flight requests finish (no fixed sleep)

## License

MIT
