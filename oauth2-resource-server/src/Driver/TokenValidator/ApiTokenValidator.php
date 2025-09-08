<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Driver\TokenValidator;

use Menumbing\OAuth2\ResourceServer\HttpClient\OAuth2ServerHttpClient;
use Menumbing\OAuth2\ResourceServer\Contract\TokenValidatorInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ApiTokenValidator implements TokenValidatorInterface
{
    protected OAuth2ServerHttpClient $client;

    public function __construct(
        ContainerInterface $container,
        array              $options,
    )
    {
        $this->client = new OAuth2ServerHttpClient(
            $container->get($options['http_client'] ?? 'oauth2')
        );
    }

    public function isTokenRevoked(string $tokenId): bool
    {
        return $this->client->tokenValidity($tokenId)['revoked'] ?? true;
    }
}