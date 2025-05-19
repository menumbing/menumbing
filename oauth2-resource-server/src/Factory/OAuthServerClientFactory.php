<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Factory;

use Hyperf\Contract\ConfigInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Menumbing\OAuth2\Resource\Client\OAuthServerClient;
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