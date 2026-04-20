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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Framework\Event\OnManagerStart;
use Hyperf\Process\ProcessCollector;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Swoole\Constant;
use Swoole\Process;

/**
 * Configures Swoole for graceful process-aware shutdown.
 *
 * Shutdown flow in SWOOLE_BASE mode:
 *
 * Host (Ctrl+C) path:
 *  1. Ctrl+C sends SIGINT to the entire process group
 *  2. SIGINT is blocked at kernel level via sigprocmask in all Swoole
 *     processes (master, workers, custom processes). This prevents
 *     Swoole's C-level SIGINT handler from calling swoole_event_exit()
 *     which would kill in-flight connections immediately.
 *  3. A dedicated signal forwarder process (which unblocks SIGINT)
 *     catches it, sets the shared shutdown flag, sends SIGTERM to master
 *  4. SIGTERM arrives at master - Swoole's C-level handler coordinates
 *     shutdown with manager (prevents reload loop)
 *  5. Manager cascades SIGTERM to all workers and custom processes
 *  6. GracefulWorkerStopHandler catches SIGTERM via waitSignal() in ALL
 *     workers (including Worker#0), polls connection_num (allowing
 *     in-flight HTTP to finish), then stops
 *  7. Custom processes detect shutdown via ShutdownWatcherListener timer
 *  8. Second Ctrl+C force-kills the process group (SIGKILL)
 *
 * Docker (SIGTERM) path:
 *  1. Docker sends SIGTERM to PID 1 (Worker#0 in BASE mode)
 *  2. Swoole's C-level SIGTERM handler triggers proper shutdown for PID 1
 *  3. Workers 1-N receive SIGTERM -> GracefulWorkerStopHandler -> drain
 *  4. onShutdown fires, forwarder cleaned up
 *
 * IMPORTANT: No Process::signal() calls are registered anywhere. This
 * ensures waitSignal() works in ALL workers including Worker#0, allowing
 * GracefulWorkerStopHandler to properly drain in-flight HTTP requests.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GracefulShutdownListener implements ListenerInterface
{
    private const DEFAULT_TIMEOUT = 300;

    private int $forwarderPid = 0;

    public function __construct(
        private readonly ConfigInterface $config,
    ) {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
            OnManagerStart::class,
            AfterWorkerStart::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof AfterWorkerStart) {
            // Override Swoole's C-level SIGINT handler with SIG_IGN after the
            // server has started each worker. Swoole installs its own SIGINT
            // handler via sigaction() during $server->start() which overrides
            // our earlier pcntl_signal(SIGINT, SIG_IGN). By re-installing
            // SIG_IGN here (after Swoole), any pending SIGINT is discarded
            // rather than triggering swoole_event_exit() which would kill
            // in-flight connections on Worker#0 (master process).
            pcntl_signal(SIGINT, SIG_IGN);
            // Re-apply sigprocmask block. Swoole may clear the signal mask
            // during worker initialization (swServer_signal_init). This
            // ensures SIGINT remains blocked at kernel level in all workers.
            pcntl_sigprocmask(SIG_BLOCK, [SIGINT]);
            return;
        }

        if ($event instanceof OnManagerStart) {
            $this->onManagerStart($event);
            return;
        }

        // Clear early pcntl signal handlers registered by bootstrap.php
        // and Symfony Console's SignalRegistry.
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(false);
            pcntl_signal(SIGTERM, SIG_DFL);
            pcntl_signal(SIGINT, SIG_IGN);

            // Block SIGINT at kernel level. Unlike pcntl_signal(SIG_IGN),
            // sigprocmask cannot be overridden by Swoole's sigaction() in
            // $server->start(). This prevents Swoole's C-level SIGINT
            // handler from calling swoole_event_exit() which would kill
            // in-flight connections immediately. The signal mask is
            // inherited by all forked processes (workers, custom processes).
            pcntl_sigprocmask(SIG_BLOCK, [SIGINT]);
        }

        /** @var BeforeMainServerStart $event */
        $timeout = (int) $this->config->get(
            'graceful_process.timeout',
            self::DEFAULT_TIMEOUT,
        );

        // Disable Swoole's deadlock check. During graceful shutdown,
        // Hyperf's SignalManager coroutines (waitSignal) are intentionally
        // left sleeping while workers and custom processes exit. Swoole
        // misidentifies this as a deadlock and logs FATAL ERROR messages.
        // This setting is inherited by all forked workers/custom processes.
        \Swoole\Coroutine::set(['enable_deadlock_check' => false]);

        // Initialize shared memory shutdown flag BEFORE fork so it's
        // inherited by all child processes (including signal forwarder).
        GracefulShutdownCollector::initShutdownFlag();

        // Initialize process counter for tracking alive custom processes.
        GracefulShutdownCollector::initProcessCounter(getmypid());

        // Set Swoole's C-level max_wait_time as a safety net.
        // Suppress Swoole WARNING-level logs (e.g. ReactorKqueue fd
        // re-registration warnings during shutdown) that are harmless
        // during graceful shutdown.
        $event->server->set([
            Constant::OPTION_MAX_WAIT_TIME => $timeout,
            Constant::OPTION_LOG_LEVEL => SWOOLE_LOG_ERROR,
        ]);

        // Fork a signal forwarder that catches SIGINT (Ctrl+C) and sends
        // SIGTERM to the master. All Swoole processes have SIGINT blocked
        // via sigprocmask so only this forwarder (which unblocks it) reacts.
        $this->forkSignalForwarder();

        $server = $event->server;
        $forwarderPid = $this->forwarderPid;

        // Clean up forwarder on server shutdown.
        // This also covers the Docker SIGTERM path.
        $server->on('shutdown', function () use ($forwarderPid) {
            if ($forwarderPid > 0) {
                @posix_kill($forwarderPid, SIGTERM);
            }
        });

        $mainPid = getmypid();

        // Fallback shutdown function: sets the atomic flag, sends SIGTERM
        // to children, and reaps the forwarder process.
        register_shutdown_function(function () use ($mainPid, $forwarderPid) {
            if (getmypid() !== $mainPid) {
                return;
            }

            GracefulShutdownCollector::requestShutdown();
            $this->sendTermSignalToChildren();

            if ($forwarderPid > 0) {
                @posix_kill($forwarderPid, SIGTERM);
                pcntl_waitpid($forwarderPid, $status, WNOHANG);
            }
        });
    }

    /**
     * Fork a dedicated signal forwarder process.
     *
     * The forwarder unblocks SIGINT (blocked in all other processes) and
     * catches it:
     *  - First SIGINT: sets shutdown flag, sends SIGTERM to master immediately
     *  - Second SIGINT: sends SIGKILL to process group (force kill)
     *
     * The forwarder is killed via SIGTERM when the server shuts down.
     */
    private function forkSignalForwarder(): void
    {
        $masterPid = getmypid();
        $pid = pcntl_fork();

        if ($pid === -1) {
            return;
        }

        if ($pid === 0) {
            // Child: signal forwarder process
            // Close inherited listening socket - forwarder doesn't serve HTTP
            $this->closeInheritedListeningFds();

            // Unblock SIGINT so this forwarder can catch Ctrl+C.
            // All other processes (master, workers) keep SIGINT blocked.
            pcntl_sigprocmask(SIG_UNBLOCK, [SIGINT]);

            $sigintCount = 0;
            pcntl_async_signals(true);

            pcntl_signal(SIGINT, function () use (&$sigintCount, $masterPid) {
                $sigintCount++;
                if ($sigintCount === 1) {
                    // First Ctrl+C: set shutdown flag and send SIGTERM
                    // to master immediately. Swoole's C-level SIGTERM
                    // handler cascades to workers via the manager.
                    // GracefulWorkerStopHandler drains in-flight HTTP.
                    GracefulShutdownCollector::requestShutdown();
                    posix_kill($masterPid, SIGTERM);
                } else {
                    // Second Ctrl+C: force kill entire process group
                    posix_kill(0, SIGKILL);
                }
            });

            $running = true;

            pcntl_signal(SIGTERM, function () use (&$running) {
                $running = false;
            });

            while ($running) {
                sleep(60);
            }

            // Use _exit() via FFI to avoid Swoole\ExitException that
            // PHP's exit() triggers in Swoole-hooked processes.
            if (extension_loaded('ffi')) {
                try {
                    \FFI::cdef('void _exit(int status);')->_exit(0);
                } catch (\Throwable) {
                }
            }
            exit(0);
        }

        // Parent: store forwarder PID for cleanup
        $this->forwarderPid = $pid;
    }

    /**
     * Close inherited listening socket fds using FFI.
     */
    private function closeInheritedListeningFds(): void
    {
        if (extension_loaded('ffi')) {
            try {
                $ffi = \FFI::cdef('int close(int fd);');
                for ($fd = 5; $fd <= 10; $fd++) {
                    @$ffi->close($fd);
                }
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Close inherited listening socket in the manager process.
     */
    private function onManagerStart(OnManagerStart $event): void
    {
        foreach ($event->server->ports as $port) {
            if (($fd = $port->sock) > 0) {
                ShutdownWatcherListener::closeFd($fd);
            }
        }
    }

    /**
     * Send SIGTERM to all known child processes.
     */
    private function sendTermSignalToChildren(): void
    {
        foreach (ProcessCollector::all() as $process) {
            if ($process->pid) {
                try {
                    Process::kill($process->pid, SIGTERM);
                } catch (\Throwable) {
                }
            }
        }
    }
}
