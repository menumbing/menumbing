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
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Process\ProcessCollector;
use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Swoole\Constant;
use Swoole\Process;

/**
 * Configures Swoole for graceful process-aware shutdown.
 *
 * Shutdown flow in SWOOLE_BASE mode:
 *  1. SIGTERM (or SIGINT) arrives -> Swoole sends SIGTERM to workers/processes
 *  2. GracefulWorkerStopHandler polls connection_num
 *     (giving in-flight HTTP requests time to finish), then calls $server->stop()
 *  3. Custom processes finish their work and exit one by one
 *  4. When the LAST process (worker or custom) exits, SIGINT is sent to master
 *     (via the process counter in GracefulShutdownCollector)
 *  5. SIGINT interrupts Swoole's internal wait, causing immediate exit
 *  6. Master exits cleanly - container stops
 *
 * SIGINT at the master level (e.g., Ctrl+C or `docker compose kill -s SIGINT`)
 * is converted to $server->shutdown(), which triggers the same SIGTERM flow.
 *
 * OPTION_MAX_WAIT_TIME is set to graceful_process.timeout as a safety net.
 * In normal operation, SIGINT fires well before this timeout expires.
 * If a process is stuck, Swoole force-kills it after the timeout.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GracefulShutdownListener implements ListenerInterface
{
    private const DEFAULT_TIMEOUT = 300;

    public function __construct(
        private readonly ConfigInterface $config,
    ) {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        /** @var BeforeMainServerStart $event */
        $timeout = (int) $this->config->get(
            'graceful_process.timeout',
            self::DEFAULT_TIMEOUT,
        );

        // Initialize shared memory shutdown flag BEFORE $server->start()
        // so it's inherited by all child processes via fork().
        GracefulShutdownCollector::initShutdownFlag();

        // Initialize process counter for early exit via SIGINT.
        GracefulShutdownCollector::initProcessCounter(getmypid());

        // Set Swoole's C-level max_wait_time as a safety net.
        // In normal operation, SIGINT from the last process (worker or custom)
        // causes exit well before this timeout expires.
        // Worker-level wait time is handled by GracefulWorkerStopHandler
        // which reads graceful_process.max_wait_time directly.
        $event->server->set([
            Constant::OPTION_MAX_WAIT_TIME => $timeout,
        ]);

        // Register SIGINT handler in the master process. In SWOOLE_BASE mode,
        // the onStart callback fires after workers are forked and the event
        // loop starts. Swoole\Process::signal() integrates with the event loop
        // so it works even after pcntl_signal() becomes ineffective.
        // This converts Ctrl+C / kill -INT into a proper shutdown sequence.
        $server = $event->server;
        $server->on('start', function () use ($server) {
            Process::signal(SIGINT, function () use ($server) {
                $server->shutdown();
            });
        });

        $mainPid = getmypid();

        // Fallback shutdown function: sets the atomic flag and sends SIGTERM
        // to children. In normal operation, SIGINT from the last child process
        // causes exit before this function fires. This is a safety net for
        // edge cases (no custom processes, or stuck process timeout).
        register_shutdown_function(function () use ($mainPid) {
            if (getmypid() !== $mainPid) {
                return;
            }

            GracefulShutdownCollector::requestShutdown();
            $this->sendTermSignalToChildren();
        });
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
