<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Process\ProcessManager;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Menumbing\Outbox\Process\OutboxRelayProcess;
use Psr\Container\ContainerInterface;

/**
 * Registers the outbox relay worker process on server start.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
final class RegisterOutboxWorker implements ListenerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $config = $this->container->get(ConfigInterface::class);

        if (!$config->get('outbox.worker.enabled', true)) {
            return;
        }

        $process = $this->container->get(OutboxRelayProcess::class);
        $process->name = 'outbox-relay';
        $process->nums = $config->get('outbox.worker.nums', 1);

        ProcessManager::register($process);
    }
}
