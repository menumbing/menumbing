<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\User;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use Menumbing\OAuth2\ResourceServer\Contract\User;
use Menumbing\OAuth2\ResourceServer\Provider\AbstractOAuth2Provider;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class DatabaseUserProvider extends AbstractOAuth2Provider
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionResolverInterface $connectionResolver, array $options)
    {
        $connection = $options['connection'] ?? 'default';

        $this->connection = $connectionResolver->connection($connection);
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        $userData = $this->connection->table('users')->find($identifier);

        if (null === $userData) {
            throw new AuthorizationException('User not found or no longer active.', 401);
        }

        return new User((array)$userData);
    }
}