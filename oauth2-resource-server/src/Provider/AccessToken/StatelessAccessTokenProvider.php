<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\AccessToken;

use Menumbing\OAuth2\ResourceServer\Contract\AccessToken;
use Menumbing\OAuth2\ResourceServer\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\ResourceServer\Contract\OAuthAccessTokenInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class StatelessAccessTokenProvider implements AccessTokenProviderInterface
{
    public function retrieveByToken(string $tokenId, string $token): ?OAuthAccessTokenInterface
    {
        return new AccessToken($tokenId, $token);
    }

    public function isTokenRevoked(string $tokenId): bool
    {
        return false;
    }
}