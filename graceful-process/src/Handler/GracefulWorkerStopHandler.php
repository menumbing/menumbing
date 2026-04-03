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
        // If SIGINT arrives while shutdown is already in progress, force-stop
        // immediately. This covers two scenarios:
        //  - Internal SIGINT sent by the counter when all processes are done
        //  - User pressing Ctrl+C a second time (force quit)
        if ($signal === SIGINT && GracefulShutdownCollector::isShutdownRequested()) {
            $this->container->get(Server::class)->stop();
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

        $server->stop();
    }
}
