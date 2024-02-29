<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Security;

use HyperfExtension\Auth\Contracts\AuthManagerInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(protected AuthManagerInterface $authManager, protected array $guards)
    {
    }

    public function isLogged(): bool
    {
        foreach ($this->guards as $guard) {
            if ($this->authManager->guard($guard)->check()) {
                return true;
            }
        }

        return false;
    }

    public function getUser(): object|null
    {
        foreach ($this->guards as $guard) {
            if (null !== $user = $this->authManager->guard($guard)->user()) {
                return $user;
            }
        }

        return null;
    }
}
