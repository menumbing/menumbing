<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Factory;

use Hyperf\Contract\ConfigInterface;
use Menumbing\OAuth2\ResourceServer\Bridge\Repository\AccessTokenRepository;
use Menumbing\OAuth2\ResourceServer\Provider\AccessToken\AccessTokenProviderResolverTrait;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class AccessTokenRepositoryFactory
{
    use AccessTokenProviderResolverTrait;

    protected ConfigInterface $config;

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function __invoke()
    {
        $providerName = $this->config->get('oauth2_resource_server.access_token.default');

        return new AccessTokenRepository($this->resolveAccessTokenProvider($providerName));
    }
}