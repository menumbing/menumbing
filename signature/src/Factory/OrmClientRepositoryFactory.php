<?php

declare(strict_types=1);

namespace Menumbing\Signature\Factory;

use Menumbing\Orm\Contract\RepositoryFactoryInterface;
use Menumbing\Signature\Model\Client;
use Menumbing\Signature\Repository\ClientRepository;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class OrmClientRepositoryFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $repositoryFactory = $container->get(RepositoryFactoryInterface::class);

        return $repositoryFactory->create(Client::class, ClientRepository::class);
    }
}
