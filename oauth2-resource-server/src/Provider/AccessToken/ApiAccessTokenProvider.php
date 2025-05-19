<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Provider\AccessToken;

use Menumbing\OAuth2\Resource\Client\OAuthServerClient;
use Menumbing\OAuth2\Resource\Contract\AccessToken;
use Menumbing\OAuth2\Resource\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\Resource\Contract\OAuthAccessTokenInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiAccessTokenProvider implements AccessTokenProviderInterface
{
    public function __construct(protected OAuthServerClient $client)
    {
    }

    public function retrieveByToken(string $tokenId, string $token): ?OAuthAccessTokenInterface
    {
        return new AccessToken($tokenId, $token);
    }

    public function isTokenRevoked(string $tokenId): bool
    {
        return $this->client->tokenValidity($tokenId)['revoked'] ?? true;
    }
}