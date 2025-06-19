<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use BadMethodCallException;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\Contract\ClientProviderInterface;

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
        return new Client(['id' => $identifier]);
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