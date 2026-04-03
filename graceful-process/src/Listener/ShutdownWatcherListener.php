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

namespace Menumbing\GracefulProcess\Listener;

use Closure;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Process\Event\BeforeProcessHandle;
use Hyperf\Process\ProcessManager;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Swoole\Timer;

/**
 * Monitors the shared shutdown flag in child processes and manages
 * the process counter for early master exit.
 *
 * On process start:
 *  - Increments the alive process counter (shared memory)
 *  - Registers a PHP shutdown function to decrement on exit
 *  - Starts a timer to detect shutdown via the atomic flag
 *
 * When shutdown is detected (timer fires):
 *  - Sets ProcessManager::setRunning(false) to break process loops
 *  - Sets restartInterval and recvTimeout to 0 to speed up process exit
 *
 * When the last custom process exits (counter reaches 0):
 *  - Sends SIGINT to the master to interrupt Swoole's hard sleep
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ShutdownWatcherListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BeforeProcessHandle::class,
        ];
    }

    public function process(object $event): void
    {
        /** @var BeforeProcessHandle $event */
        $abstractProcess = $event->process;

        // Track this process in the shared counter
        GracefulShutdownCollector::registerProcess();

        // Decrement counter when this process exits. If it's the last
        // process during shutdown, this triggers SIGINT to the master.
        register_shutdown_function(function () {
            GracefulShutdownCollector::unregisterProcess();
        });

        // Poll for shutdown flag every 500ms
        Timer::tick(500, function (int $timerId) use ($abstractProcess) {
            if (GracefulShutdownCollector::isShutdownRequested()) {
                ProcessManager::setRunning(false);

                // Speed up process exit by zeroing internal delays:
                // - restartInterval: skips the 5s sleep in finally block
                // - recvTimeout: prevents future recv() calls from blocking
                // - socket close: interrupts the current blocked recv() call
                Closure::bind(function () {
                    $this->restartInterval = 0;
                    $this->recvTimeout = 0.001;
                    if ($this->process) {
                        try {
                            $this->process->exportSocket()->close();
                        } catch (\Throwable) {
                        }
                    }
                }, $abstractProcess, $abstractProcess::class)();

                Timer::clear($timerId);
            }
        });
    }
}
