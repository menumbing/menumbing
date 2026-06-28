<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Storage;

use Hyperf\Contract\ConfigInterface;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory that resolves the configured outbox storage implementation.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxStorageFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function __invoke(): OutboxStorageInterface
    {
        $config = $this->container->get(ConfigInterface::class);
        $class = $config->get('outbox.storage.class', DatabaseOutboxStorage::class);

        return $this->container->get($class);
    }
}
