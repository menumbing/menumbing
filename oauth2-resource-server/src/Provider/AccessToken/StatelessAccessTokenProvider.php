<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Provider\AccessToken;

use Menumbing\OAuth2\Resource\Contract\AccessToken;
use Menumbing\OAuth2\Resource\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\Resource\Contract\OAuthAccessTokenInterface;

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