<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Factory;

use Hyperf\Contract\ConfigInterface;
use Menumbing\OAuth2\ResourceServer\Client\OAuthServerClient;
use Menumbing\OAuth2\ResourceServer\Contract\AccessTokenProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuthServerClientFactory
{
    protected ConfigInterface $config;

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
    }

    public function __invoke()
    {
        return new OAuthServerClient(
            $this->container->get(
                $this->config->get('oauth2-resource-server.oauth_server_http_client')
            )
        );
    }
}