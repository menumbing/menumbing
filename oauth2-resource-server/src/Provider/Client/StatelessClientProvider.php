<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\Client;

use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Contract\Client;
use Menumbing\OAuth2\ResourceServer\Provider\AbstractOAuth2Provider;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class StatelessClientProvider extends AbstractOAuth2Provider
{
    public function retrieveByToken($identifier, string $token): ?AuthenticatableInterface
    {
        return new Client(['id' => $identifier]);
    }
}