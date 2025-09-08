<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\User;

use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Contract\User;
use Menumbing\OAuth2\ResourceServer\Provider\AbstractOAuth2Provider;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class StatelessUserProvider extends AbstractOAuth2Provider
{
    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new User(['id' => $identifier]);
    }
}