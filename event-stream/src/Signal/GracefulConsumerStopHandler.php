<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Signal;

use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Process\ProcessManager;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Signal(priority: PHP_INT_MAX)]
class GracefulConsumerStopHandler implements SignalHandlerInterface
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
        CoordinatorManager::until(Constants::WORKER_EXIT)->resume();
        ProcessManager::setRunning(false);
    }
}
