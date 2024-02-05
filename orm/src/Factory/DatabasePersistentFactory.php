<?php

declare(strict_types=1);

namespace Menumbing\Orm\Factory;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DatabasePersistentFactory
{
    public function __invoke(ContainerInterface $container, array $parameter = [])
    {
        $config = $container->get(ConfigInterface::class);

        $persistentClass = $config->get('orm.persistent.class');

        $middlewares = $config->get('orm.persistent.middleware');

        return make($persistentClass, compact('middlewares'));
    }
}
