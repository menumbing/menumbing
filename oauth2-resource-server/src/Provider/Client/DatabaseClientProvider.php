<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use BadMethodCallException;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\Contract\ClientProviderInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class DatabaseClientProvider implements ClientProviderInterface
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionResolverInterface $connectionResolver, array $options)
    {
        $connection = $options['connection'] ?? 'default';

        $this->connection = $connectionResolver->connection($connection);
    }

    public function retrieveById($identifier): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        $userData = $this->connection->table('oauth_clients')->find($identifier);

        if (null === $userData) {
            throw new AuthorizationException('Client not found or no longer active.', 401);
        }

        return new Client((array)$userData);
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