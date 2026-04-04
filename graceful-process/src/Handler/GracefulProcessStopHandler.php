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

use Hyperf\Process\ProcessManager;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Menumbing\GracefulProcess\GracefulShutdownCollector;

/**
 * Generic signal handler that blocks process exit until gracefully-managed
 * work completes.
 *
 * When no channels are registered (process doesn't use GracefulShutdown trait),
 * this handler simply sets isRunning to false - same as the default ProcessStopHandler.
 *
 * When channels are registered (Tier 2), it blocks on each channel until the
 * process's runGracefully() callback finishes.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Signal(priority: PHP_INT_MAX)]
class GracefulProcessStopHandler implements SignalHandlerInterface
{
    public function listen(): array
    {
        return [
            [self::PROCESS, SIGTERM],
            [self::PROCESS, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        ProcessManager::setRunning(false);

        foreach (GracefulShutdownCollector::getChannels() as $channel) {
            $channel->pop(-1);
        }
    }
}
