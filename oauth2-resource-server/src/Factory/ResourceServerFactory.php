<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Factory;

use Hyperf\Contract\ConfigInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Menumbing\OAuth2\ResourceServer\MakeCryptKey;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ResourceServerFactory
{
    use MakeCryptKey;

    protected ConfigInterface $config;

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function __invoke()
    {
        return new ResourceServer(
            $this->container->get(AccessTokenRepositoryInterface::class),
            $this->makeKey($this->config->get('oauth2_resource_server.public_key')),
        );
    }
}
