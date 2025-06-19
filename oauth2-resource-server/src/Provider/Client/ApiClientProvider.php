<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use BadMethodCallException;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Client\OAuthServerClient;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\Contract\ClientProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiClientProvider implements ClientProviderInterface
{
    protected OAuthServerClient $client;
    public function __construct(
        ContainerInterface $container,
        array              $options,
    )
    {
        $this->client = new OAuthServerClient(
            $container->get($options['http_client'] ?? 'oauth2')
        );
    }

    public function retrieveById($identifier): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new Client(
            $this->client->clientDetail($identifier, $token)
        );
    }

    public function updateRememberToken(AuthenticatableInterface $user, string $token): void
    {
        throw new BadMethodCallException();
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        throw new BadMethodCallException();
    }
}