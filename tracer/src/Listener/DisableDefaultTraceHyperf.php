<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Listener;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Tracer\SwitchManager;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DisableDefaultTraceHyperf implements ListenerInterface
{
    #[Inject]
    protected ContainerInterface $container;

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $switchManager = $this->container->get(SwitchManager::class);

        $switchManager->apply([
            'guzzle' => false,
            'db' => false,
            'redis' => false,
        ]);
    }
}
