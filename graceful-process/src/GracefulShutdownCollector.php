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

namespace Menumbing\GracefulProcess;

use Hyperf\Engine\Channel;
use Swoole\Atomic;

/**
 * Static registry for completion channels, shutdown flags, and process counters.
 *
 * Uses Swoole\Atomic (shared memory) for cross-process communication:
 * - Shutdown flag: notifies child processes that shutdown is requested
 * - Process counter: tracks alive custom processes for early master exit
 *
 * Channels are per-process (isolated after fork) and used by the
 * GracefulShutdown trait for coroutine coordination.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GracefulShutdownCollector
{
    /**
     * @var Channel[]
     */
    protected static array $channels = [];

    /**
     * Shared memory atomic flag for cross-process shutdown notification.
     * Value 0 = running, 1 = shutdown requested.
     */
    protected static ?Atomic $shutdownFlag = null;

    /**
     * Shared memory counter tracking alive custom processes.
     * Incremented on process start, decremented on process exit.
     * When it reaches 0 during shutdown, SIGINT is sent to the master.
     */
    protected static ?Atomic $processCounter = null;

    /**
     * Separate counter for custom processes only, used by SWOOLE_PROCESS
     * master to know when all custom processes have exited. The main
     * processCounter also includes workers (which don't reliably
     * unregister in SWOOLE_PROCESS mode on Linux).
     */
    protected static ?Atomic $customProcessCounter = null;

    /**
     * Master process PID, saved before fork so children can signal it.
     */
    protected static int $masterPid = 0;

    public static function register(Channel $channel): void
    {
        static::$channels[] = $channel;
    }

    /**
     * @return Channel[]
     */
    public static function getChannels(): array
    {
        return static::$channels;
    }

    /**
     * Initialize the shared shutdown flag. Must be called BEFORE
     * $server->start() (i.e., before any fork) so the shared memory
     * is inherited by all child processes.
     */
    public static function initShutdownFlag(): void
    {
        if (static::$shutdownFlag === null) {
            static::$shutdownFlag = new Atomic(0);
        }
    }

    /**
     * Signal that shutdown has been requested (set by main process).
     */
    public static function requestShutdown(): void
    {
        static::$shutdownFlag?->set(1);
    }

    /**
     * Check whether shutdown has been requested (read by child processes).
     */
    public static function isShutdownRequested(): bool
    {
        return static::$shutdownFlag !== null && static::$shutdownFlag->get() === 1;
    }

    /**
     * Initialize the process counter. Must be called BEFORE $server->start()
     * so the shared memory is inherited by all child processes.
     */
    public static function initProcessCounter(int $masterPid): void
    {
        static::$processCounter = new Atomic(0);
        static::$customProcessCounter = new Atomic(0);
        static::$masterPid = $masterPid;
    }

    /**
     * Get the current process count (number of alive registered processes).
     */
    public static function getProcessCount(): int
    {
        return static::$processCounter?->get() ?? 0;
    }

    /**
     * Register a custom process as alive (called in the child process on start).
     */
    public static function registerProcess(): void
    {
        static::$processCounter?->add(1);
    }

    /**
     * Register a custom process in the dedicated custom process counter.
     * Used by SWOOLE_PROCESS master to track custom process completion
     * independently from workers.
     */
    public static function registerCustomProcess(): void
    {
        static::$customProcessCounter?->add(1);
    }

    /**
     * Get the number of alive custom processes (dedicated counter).
     */
    public static function getCustomProcessCount(): int
    {
        return static::$customProcessCounter?->get() ?? 0;
    }

    /**
     * Unregister a custom process (called in the child process on exit).
     *
     * When the counter reaches 0 during shutdown, sends SIGINT to the
     * master process to interrupt Swoole's hard sleep and trigger
     * immediate exit.
     */
    public static function unregisterProcess(): void
    {
        if (static::$processCounter === null) {
            return;
        }

        $remaining = static::$processCounter->sub(1);
        static::$customProcessCounter?->sub(1);

        if ($remaining === 0 && static::isShutdownRequested() && static::$masterPid > 0) {
            posix_kill(static::$masterPid, SIGINT);
        }
    }
}
