<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Security;

use HyperfExtension\Auth\Contracts\Access\GateManagerInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(protected GateManagerInterface $gate, protected AuthenticationServiceInterface $authenticationService)
    {
    }

    public function isAllowed(string $right, mixed $subject = null): bool
    {
        return $this->gate->forUser($this->authenticationService->getUser())->check($right, $subject);
    }
}
