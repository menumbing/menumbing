<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Provider\User;

use BadMethodCallException;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Contracts\UserProviderInterface;
use HyperfExtension\Auth\GenericUser;
use Menumbing\OAuth2\Resource\Client\OAuthServerClient;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiUserProvider implements UserProviderInterface
{
    public function __construct(protected OAuthServerClient $client, array $options)
    {
    }

    public function retrieveById($identifier): ?AuthenticatableInterface
    {
        throw new BadMethodCallException();
    }

    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new GenericUser(
            $this->client->userInfo($token)
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