<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider;

use BadMethodCallException;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Contracts\UserProviderInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
abstract class AbstractOAuth2Provider implements UserProviderInterface
{
    public function retrieveById($identifier): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
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