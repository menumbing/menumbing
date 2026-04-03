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

namespace Menumbing\GracefulProcess\Trait;

use Hyperf\Engine\Channel;
use Menumbing\GracefulProcess\GracefulShutdownCollector;

/**
 * Provides graceful shutdown support for any AbstractProcess subclass.
 *
 * Usage:
 *   use GracefulShutdown;
 *
 *   public function handle(): void
 *   {
 *       $this->runGracefully(function () {
 *           while (ProcessManager::isRunning()) {
 *               $this->doWork();
 *           }
 *       });
 *   }
 *
 * When SIGTERM is received, the GracefulProcessStopHandler blocks on
 * the registered channel until the callback completes, ensuring
 * in-flight work finishes before the process exits.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait GracefulShutdown
{
    protected function runGracefully(callable $callback): void
    {
        $channel = new Channel(1);

        GracefulShutdownCollector::register($channel);

        try {
            $callback();
        } finally {
            $channel->close();
        }
    }
}
