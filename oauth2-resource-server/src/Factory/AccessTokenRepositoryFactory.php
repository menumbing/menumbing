<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Factory;

use Hyperf\Contract\ConfigInterface;
use Menumbing\OAuth2\ResourceServer\Bridge\Repository\AccessTokenRepository;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class AccessTokenRepositoryFactory
{
    protected ConfigInterface $config;

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function __invoke()
    {
        $providerName = $this->config->get('oauth2-resource-server.access_token.provider');

        return new AccessTokenRepository(
            $this->container->get(
                $this->config->get('oauth2-resource-server.access_token.providers.' . $providerName)
            )
        );
    }
}