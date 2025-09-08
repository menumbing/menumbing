<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\Provider\AbstractOAuth2Provider;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class DatabaseClientProvider extends AbstractOAuth2Provider
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionResolverInterface $connectionResolver, array $options)
    {
        $connection = $options['connection'] ?? 'default';

        $this->connection = $connectionResolver->connection($connection);
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        $clientData = $this->connection->table('oauth_clients')->find($identifier);

        if (null === $clientData) {
            throw new AuthorizationException('Client not found or no longer active.', 401);
        }

        return new Client((array)$clientData);
    }
}