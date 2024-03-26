<?php

declare(strict_types=1);

namespace Menumbing\Signature\Factory;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ClientRepositoryFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $factory = $config->get('signature.client_repository_factory');

        if (is_string($factory)) {
            $factory = $container->get($factory);
        }

        return $factory($container);
    }
}
