<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Bridge\Repository;

use BadMethodCallException;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Menumbing\OAuth2\ResourceServer\Contract\TokenValidatorInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(protected TokenValidatorInterface $tokenValidator)
    {
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, ?string $userIdentifier = null): AccessTokenEntityInterface
    {
        throw new BadMethodCallException();
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        throw new BadMethodCallException();
    }

    public function revokeAccessToken(string $tokenId): void
    {
        throw new BadMethodCallException();
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->tokenValidator->isTokenRevoked($tokenId);
    }
}