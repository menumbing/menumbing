<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class StandardServerFactory
{
    public function __invoke(ContainerInterface $container): StandardServer
    {
        return new StandardServer($container->get(ServerConfig::class));
    }
}
