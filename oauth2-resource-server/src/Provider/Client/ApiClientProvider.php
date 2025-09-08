<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\HttpClient\OAuth2ServerHttpClient;
use Menumbing\OAuth2\ResourceServer\Provider\AbstractOAuth2Provider;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiClientProvider extends AbstractOAuth2Provider
{
    protected OAuth2ServerHttpClient $client;

    public function __construct(
        ContainerInterface $container,
        ?array             $options = null,
    )
    {
        $this->client = new OAuth2ServerHttpClient(
            $container->get($options['http_client'] ?? 'oauth2')
        );
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new Client(
            $this->client->clientDetail($identifier, $token)
        );
    }
}