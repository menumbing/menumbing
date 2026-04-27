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
use Hyperf\Framework\Event\OnStart;
use Hyperf\Process\ProcessCollector;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Swoole\Constant;
use Swoole\Process;

/**
 * Configures Swoole for graceful process-aware shutdown.
 *
 * Supports both SWOOLE_BASE and SWOOLE_PROCESS modes. The package
 * auto-detects the server mode and adjusts SIGINT protection accordingly.
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
            OnStart::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof OnStart) {
            // SWOOLE_PROCESS only: re-block SIGINT in the master process
            // after Swoole's swServer_signal_init. In PROCESS mode, the
            // master is a separate reactor process (not Worker#0), so
            // AfterWorkerStart doesn't protect it. OnStart only fires
            // in PROCESS mode — this is a no-op in BASE mode.
            pcntl_signal(SIGINT, SIG_IGN);
            pcntl_sigprocmask(SIG_BLOCK, [SIGINT]);

            // Override Swoole's C-level SIGTERM handler in the master process.
            //
            // Problem: Swoole's default SIGTERM handler calls swoole_event_exit()
            // which immediately stops the reactor event loop. In SWOOLE_PROCESS,
            // the master reactor manages all TCP connections and forwards
            // responses from workers via pipes. When the reactor exits, active
            // TCP connections are closed — clients get "empty reply" even though
            // workers are still processing in-flight requests.
            //
            // Fix: use pcntl_signal() to override the handler via sigaction()
            // (last call wins, installed after Swoole's swServer_signal_init).
            // We keep the reactor alive during worker drain, only exiting
            // after the manager exits (= all workers done). Custom processes
            // are cleaned up by Swoole's shutdown sequence after Event::exit().
            //
            // Note: Process::signal() cannot be used in OnStart — it requires
            // the Swoole event loop which isn't running yet. pcntl_signal()
            // works because it uses sigaction() directly.
            $server = $event->server;
            $timeout = (int) $this->config->get(
                'graceful_process.timeout',
                self::DEFAULT_TIMEOUT,
            );

            $shuttingDown = false;

            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () use (&$shuttingDown, $server, $timeout) {
                if ($shuttingDown) {
                    return;
                }
                $shuttingDown = true;

                GracefulShutdownCollector::requestShutdown();

                // Close listening socket — no new TCP connections accepted.
                foreach ($server->ports as $port) {
                    if (($fd = $port->sock) > 0) {
                        ShutdownWatcherListener::closeFd($fd);
                    }
                }

                // Forward SIGTERM to manager, which cascades to workers.
                // GracefulWorkerStopHandler in workers will drain in-flight
                // requests via connection_num polling before stopping.
                if ($server->manager_pid > 0) {
                    posix_kill($server->manager_pid, SIGTERM);
                }

                // Poll until all processes finish. The reactor stays alive
                // during this period, forwarding responses from worker pipes
                // to client TCP connections.
                //
                // Exit condition (OR, not AND):
                //  - Manager dead: all workers + custom processes exited.
                //    Reliable in Docker (init reaps zombies) but on bare
                //    hosts the manager becomes a zombie and posix_kill
                //    still returns true.
                //  - Custom process counter at 0: all custom processes
                //    exited via the flag mechanism. Workers typically finish
                //    before custom processes (HTTP drain is seconds, custom
                //    work is longer). Fallback for the zombie case.
                //  - Deadline: safety net.
                $deadline = time() + $timeout;
                $managerPid = $server->manager_pid;

                \Swoole\Timer::tick(500, function (int $timerId) use ($managerPid, $deadline) {
                    $managerAlive = $managerPid > 0 && @posix_kill($managerPid, 0);
                    $customAlive = GracefulShutdownCollector::getCustomProcessCount() > 0;

                    if (!$managerAlive || !$customAlive || time() >= $deadline) {
                        \Swoole\Timer::clear($timerId);
                        \Swoole\Event::exit();
                    }
                });
            });

            // Ensure pcntl_signal callbacks are dispatched during the C
            // reactor loop. The master reactor is C code — PHP signal
            // callbacks only fire during Zend VM execution. This timer
            // gives the VM regular opportunities to dispatch pending
            // signals (at most 100ms delay from signal arrival).
            \Swoole\Timer::tick(100, function (int $timerId) use (&$shuttingDown) {
                pcntl_signal_dispatch();
                if ($shuttingDown) {
                    \Swoole\Timer::clear($timerId);
                }
            });

            return;
        }

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
                @pcntl_waitpid($forwarderPid, $status, WNOHANG);
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
