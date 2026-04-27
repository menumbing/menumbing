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
use Hyperf\Server\ServerFactory;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Psr\Container\ContainerInterface;
use Swoole\Timer;

/**
 * Monitors the shared shutdown flag in child processes and manages
 * the process counter for early master exit.
 *
 * On process start:
 *  - Closes the inherited server listening socket (custom processes don't need it)
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
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

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

        // Close inherited server listening socket. Custom processes don't
        // serve HTTP and don't need this fd. Keeping it open prevents the
        // port from being released until ALL processes exit, which blocks
        // rapid restarts during graceful shutdown.
        $this->closeInheritedServerSocket();

        // Override Swoole's C-level SIGTERM handler. The manager forwards
        // SIGTERM to all custom processes during shutdown. Swoole's default
        // handler calls swoole_event_exit() -> _exit(), which skips PHP
        // shutdown functions entirely (counter never decrements).
        //
        // By installing a PHP callback via pcntl_signal() (which uses
        // sigaction, overriding Swoole's handler), we:
        //  1. Prevent _exit() — the process stays alive
        //  2. Set the shutdown flag — critical for SWOOLE_BASE where no
        //     other process sets it before SIGTERM reaches custom processes
        //
        // In SWOOLE_PROCESS, the master already sets the flag before the
        // manager forwards SIGTERM, so this is a harmless redundant set.
        // In SWOOLE_BASE, this is the PRIMARY mechanism for flag propagation.
        pcntl_signal(SIGTERM, function () {
            GracefulShutdownCollector::requestShutdown();
        });

        // Track this process in the shared counters
        GracefulShutdownCollector::registerProcess();
        GracefulShutdownCollector::registerCustomProcess();

        // Decrement counter when this process exits. If it's the last
        // process during shutdown, this triggers SIGINT to the master.
        register_shutdown_function(function () {
            GracefulShutdownCollector::unregisterProcess();
        });

        // Poll for shutdown flag every 500ms. Also dispatch pending
        // pcntl signals — since pcntl_async_signals is false (inherited
        // from the parent process), the SIGTERM handler above only fires
        // when explicitly dispatched.
        Timer::tick(500, function (int $timerId) use ($abstractProcess) {
            pcntl_signal_dispatch();

            if (GracefulShutdownCollector::isShutdownRequested()) {
                ProcessManager::setRunning(false);

                // Speed up process exit by zeroing internal delays:
                // - restartInterval: skips the 5s sleep in finally block
                // - recvTimeout: makes future recv() calls return quickly
                Closure::bind(function () {
                    $this->restartInterval = 0;
                    $this->recvTimeout = 0.001;
                }, $abstractProcess, $abstractProcess::class)();

                Timer::clear($timerId);
            }
        });
    }

    private function closeInheritedServerSocket(): void
    {
        try {
            $server = $this->container->get(ServerFactory::class)->getServer()->getServer();

            foreach ($server->ports as $port) {
                if (($fd = $port->sock) > 0) {
                    self::closeFd($fd);
                }
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Close a raw file descriptor, bypassing Swoole's PHP-level hooks.
     *
     * Swoole's coroutine runtime hooks intercept PHP stream operations
     * (fclose, socket_close, etc.) and may prevent closing server-managed
     * fds. FFI calls the C close() directly, bypassing these hooks.
     */
    public static function closeFd(int $fd): void
    {
        if (extension_loaded('ffi')) {
            try {
                \FFI::cdef('int close(int fd);')->close($fd);
                return;
            } catch (\Throwable) {
            }
        }

        // Fallback when FFI is not available
        $stream = @fopen("php://fd/{$fd}", "r");
        if (is_resource($stream)) {
            @fclose($stream);
        }
    }
}
