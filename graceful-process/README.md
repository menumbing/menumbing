# Graceful Process for Hyperf

Process-completion-aware graceful shutdown for [Hyperf](https://hyperf.io) applications running on Swoole (SWOOLE_BASE mode).

Instead of relying on fixed timeouts, this package monitors actual process and request completion. The server exits as soon as all in-flight work is done — custom processes finish their current job, and HTTP workers finish serving active requests.

## Features

- **Non-invasive** — works with existing custom processes without code changes; blocking operations (sleep, I/O, message consumption) complete naturally
- **HTTP request protection** — in-flight requests complete normally; new requests receive `503 Service Unavailable` during shutdown
- **Process-aware shutdown** — tracks custom processes and HTTP workers via shared-memory counters; exits immediately when all are done
- **SIGINT support** — Ctrl+C triggers the same graceful shutdown as SIGTERM via a dedicated signal forwarder process
- **Early signal handling** — catches SIGTERM and SIGINT arriving during application bootstrap (before Swoole's handlers are registered)
- **Clean logs** — suppresses Swoole's noisy shutdown diagnostics (deadlock warnings, ReactorKqueue errors, ExitException)
- **Zero configuration** — works out of the box with sensible defaults

## Requirements

- PHP >= 8.1
- Hyperf >= 3.1
- Swoole (SWOOLE_BASE mode)
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
     * Swoole's C-level max_wait_time is set to this value. If any process
     * is stuck, Swoole force-kills it after this duration.
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
2. New requests receive `503 Service Unavailable` with a `Retry-After: 5` header
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

For additional control, you can use the `GracefulShutdown` trait with `runGracefully()`. This registers a completion channel that `GracefulProcessStopHandler` blocks on, ensuring the callback finishes before the process exits:

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

## How It Works

### Shutdown Flow (SWOOLE_BASE mode)

#### Host (Ctrl+C / SIGINT) Path

```
Ctrl+C
  |
  +-- SIGINT sent to entire process group
  |
  +-- All Swoole processes have SIGINT blocked (sigprocmask)
  |     -> SIGINT is silently ignored in master, workers, custom processes
  |
  +-- Signal forwarder (dedicated child process, SIGINT unblocked):
  |   1. Catches SIGINT
  |   2. Sets shared shutdown flag (Swoole\Atomic)
  |   3. Sends SIGTERM to master process
  |   (Second Ctrl+C → SIGKILL to entire process group)
  |
  +-- Master receives SIGTERM → Swoole cascades to all processes
  |
  +-- Workers (GracefulWorkerStopHandler):
  |   1. Set shared shutdown flag
  |   2. GracefulShutdownMiddleware starts returning 503
  |   3. Poll connection_num until all in-flight requests finish
  |   4. $server->stop() → worker exits
  |
  +-- Custom Processes (ShutdownWatcherListener):
  |   1. Detect shutdown flag via 500ms timer
  |   2. Set ProcessManager::setRunning(false)
  |   3. Current blocking operation completes naturally
  |   4. Process exits when handle() returns
  |
  +-- When process counter reaches 0:
        -> SIGINT sent to master
        -> Interrupts Swoole's internal wait
        -> Server exits immediately
```

#### Docker (SIGTERM) Path

```
docker stop / kill -TERM
  |
  +-- SIGTERM sent to PID 1 (Worker#0 in BASE mode)
  |
  +-- Swoole's C-level SIGTERM handler triggers shutdown
  |
  +-- Same flow as above (workers drain, custom processes finish)
  |
  +-- Signal forwarder cleaned up via onShutdown callback
```

### Signal Handling

| Signal | Source | Behavior |
|--------|--------|----------|
| SIGTERM | `docker stop` / `kill` | Graceful shutdown via Swoole's C-level handler |
| SIGINT (1st) | Ctrl+C / `kill -INT` | Caught by forwarder → converted to SIGTERM |
| SIGINT (2nd) | Double Ctrl+C | Forwarder sends SIGKILL to process group |
| SIGINT (internal) | Process counter = 0 | Master exits immediately (interrupts Swoole wait) |

### Key Mechanisms

- **`pcntl_sigprocmask(SIG_BLOCK, [SIGINT])`** — blocks SIGINT at kernel level in all Swoole processes. Cannot be overridden by Swoole's `sigaction()`. Prevents Swoole's C-level SIGINT handler from calling `swoole_event_exit()` which would kill in-flight connections.
- **Signal forwarder process** — a dedicated child that unblocks SIGINT, catches Ctrl+C, and translates it to SIGTERM for the master. Avoids the need for `Process::signal()` which conflicts with `waitSignal()` in workers.
- **`AfterWorkerStart` re-block** — re-applies `pcntl_signal(SIGINT, SIG_IGN)` and `pcntl_sigprocmask(SIG_BLOCK, [SIGINT])` after Swoole's worker initialization, preventing race conditions where pending SIGINT could fire during SIGTERM processing.
- **`Swoole\Atomic` (shared memory)** — cross-process shutdown flag and process counter, inherited by all children via `fork()`.
- **Process counter** — tracks alive workers + custom processes; SIGINT fires to master only when ALL reach zero.
- **Connection polling** — workers monitor `connection_num` and exit as soon as in-flight requests finish (no fixed sleep).
- **`enable_deadlock_check => false`** — disables Swoole's false-positive deadlock detection during shutdown (Hyperf's SignalManager coroutines are intentionally left sleeping).
- **`log_level => SWOOLE_LOG_ERROR`** — suppresses Swoole WARNING-level logs (ReactorKqueue fd re-registration) during shutdown.
- **FFI `_exit(0)`** — avoids `Swoole\ExitException` from PHP's `exit()` in the forwarder process.

## License

MIT
