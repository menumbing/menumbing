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

/**
 * Early signal registration bootstrap.
 *
 * This file is loaded via Composer's "files" autoload, making it one of
 * the first PHP code to execute. It registers SIGTERM and SIGINT handlers
 * so that signals arriving during startup are caught and handled cleanly.
 *
 * WHY THIS IS NEEDED:
 * When SIGTERM or SIGINT arrives very early (e.g., `docker compose up &&
 * docker compose stop`, or rapid Ctrl+C), Swoole has not yet registered
 * its own signal handlers. Without this bootstrap handler, the signal
 * would either be ignored (PID 1 kernel protection) or kill the process
 * uncleanly.
 *
 * This handler simply calls exit(0) for a clean shutdown. Since no
 * server or child processes exist at this point, there is nothing to
 * clean up. Once Swoole's $server->start() runs, Swoole overrides this
 * handler with its own sigaction-based handler for normal shutdown flow.
 */
if (PHP_SAPI === 'cli' && function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () {
        exit(0);
    });
    pcntl_signal(SIGINT, static function () {
        exit(0);
    });
}
