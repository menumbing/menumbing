<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer;

use Menumbing\OAuth2\ResourceServer\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\ResourceServer\Contract\ClientProviderInterface;
use Menumbing\OAuth2\ResourceServer\Contract\OAuth2GuardInterface;
use Menumbing\OAuth2\ResourceServer\Contract\OAuthAccessTokenInterface;
use Menumbing\OAuth2\ResourceServer\Contract\OAuthClientInterface;

/**
 * @mixin OAuth2GuardInterface
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
trait OAuth2GuardHelpers
{
    protected ClientProviderInterface $clientProvider;
    protected AccessTokenProviderInterface $accessTokenProvider;
    protected ?OAuthClientInterface $client = null;
    protected ?OAuthAccessTokenInterface $accessToken = null;
    protected array $scopes = [];

    public function retrieveClient($identifier, string $token): ?OAuthClientInterface
    {
        $this->client = $this->clientProvider->retrieveByToken(
            identifier: $identifier,
            token     : $token
        );

        return $this->client;
    }

    public function retrieveAccessToken($identifier, string $token): ?OAuthAccessTokenInterface
    {
        $this->accessToken = $this->accessTokenProvider->retrieveByToken(
            tokenId: $identifier,
            token  : $token
        );

        return $this->accessToken;
    }

    public function client(): ?OAuthClientInterface
    {
        return $this->client;
    }

    public function tokenScopes(): array
    {
        return $this->scopes;
    }
}