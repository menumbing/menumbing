<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Provider\Client;

use BadMethodCallException;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\Resource\Contract\Client;
use Menumbing\OAuth2\Resource\Contract\ClientProviderInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class StatelessClientProvider implements ClientProviderInterface
{
    public function retrieveById($identifier): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new Client($identifier);
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