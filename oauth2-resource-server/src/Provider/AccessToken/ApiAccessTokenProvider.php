<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\AccessToken;

use Menumbing\OAuth2\ResourceServer\Client\OAuthServerClient;
use Menumbing\OAuth2\ResourceServer\Contract\AccessToken;
use Menumbing\OAuth2\ResourceServer\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\ResourceServer\Contract\OAuthAccessTokenInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiAccessTokenProvider implements AccessTokenProviderInterface
{
    protected OAuthServerClient $client;

    public function __construct(
        ContainerInterface $container,
        array              $options,
    )
    {
        $this->client = new OAuthServerClient(
            $container->get($options['http_client'] ?? 'oauth2')
        );
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