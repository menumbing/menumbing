<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Menumbing\GracefulProcess\Handler;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Hyperf\Signal\SignalManager;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;
use Swoole\Server;

/**
 * Worker-level signal handler for graceful shutdown.
 *
 * Handles both SIGTERM and SIGINT for graceful shutdown:
 *
 * On SIGTERM or external SIGINT (e.g., Ctrl+C):
 *  1. Sets the shared shutdown flag (for custom processes)
 *  2. Registers this worker in the process counter
 *  3. New requests are rejected by GracefulShutdownMiddleware (503)
 *  4. Polls connection count — exits as soon as in-flight requests finish
 *  5. Safety timeout (max_wait_time) force-stops if requests hang
 *  6. Worker exits -> shutdown function -> unregisters from counter
 *  7. When counter reaches 0, SIGINT is sent to master
 *
 * On internal SIGINT (shutdown already in progress):
 *  - Calls $server->stop() immediately (interrupts Swoole's hard sleep)
 *  - Also triggered by a second Ctrl+C (force quit)
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Signal(priority: PHP_INT_MAX)]
class GracefulWorkerStopHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function listen(): array
    {
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        // If SIGINT arrives while shutdown is already in progress, ignore it.
        // The SIGTERM handler is already draining in-flight requests via the
        // connection_num polling loop. Calling $server->stop() here would
        // kill connections immediately (race condition when Ctrl+C sends
        // SIGINT to entire process group and it reaches Worker#0 before
        // the drain completes). Force-quit is handled by the forwarder
        // (second Ctrl+C → SIGKILL to process group).
        if ($signal === SIGINT && GracefulShutdownCollector::isShutdownRequested()) {
            return;
        }

        // First SIGTERM or first SIGINT (external, e.g. Ctrl+C):
        // Begin graceful shutdown.
        GracefulShutdownCollector::requestShutdown();

        // Register this worker so the master's SIGINT only fires when ALL
        // workers and custom processes have exited.
        GracefulShutdownCollector::registerProcess();

        register_shutdown_function(function () {
            GracefulShutdownCollector::unregisterProcess();
        });

        // Tell Hyperf's signal loop to stop. Other signal coroutines
        // (e.g. SIGINT waitSignal) will exit when their current
        // waitSignal() call times out (up to 5s), preventing Swoole's
        // "all coroutines are sleeping - Loss deadlock" error.
        if ($this->container->has(SignalManager::class)) {
            $this->container->get(SignalManager::class)->setStopped(true);
        }

        $server = $this->container->get(Server::class);
        $config = $this->container->get(ConfigInterface::class);
        $timeout = (int) $config->get('graceful_process.timeout', 300);
        $maxWaitTime = (int) $config->get('graceful_process.max_wait_time', $timeout);

        // Poll until all in-flight requests complete (connection_num == 0)
        // or max_wait_time expires. New requests arriving during this period
        // are rejected with 503 by GracefulShutdownMiddleware.
        // Coroutine::sleep yields to the event loop, allowing HTTP handler
        // coroutines to continue running.
        $deadline = time() + $maxWaitTime;
        while (time() < $deadline) {
            if (($server->stats()['connection_num'] ?? 0) === 0) {
                break;
            }
            Coroutine::sleep(0.5);
        }

        // Disable deadlock check — signal coroutines from Hyperf's
        // SignalManager are intentionally left sleeping (they will be
        // cleaned up when the worker process exits). Without this,
        // Swoole logs "all coroutines are asleep - deadlock!" errors.
        Coroutine::set(['enable_deadlock_check' => false]);

        $server->stop();
    }
}
