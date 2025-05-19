<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Contract;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
interface AccessTokenProviderInterface
{
    public function retrieveByToken(string $tokenId, string $token): ?OAuthAccessTokenInterface;

    public function isTokenRevoked(string $tokenId): bool;
}