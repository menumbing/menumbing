<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Guard;

use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\OAuth2\ResourceServer\Util\RequestExtractor;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuth2ClientGuard extends AbstractOAuth2Guard
{
    protected function authenticateWithBearerToken(ServerRequestInterface $request): ?AuthenticatableInterface
    {
        $bearerToken = RequestExtractor::bearerToken($request);
        $request = $this->authenticator->authenticateRequest($request);

        $this->validateScopes($request);

        if (null === $client = $this->provider->retrieveByToken($request->getAttribute('oauth_client_id'), $bearerToken)) {
            return null;
        }

        if (null === $this->retrieveAccessToken($request->getAttribute('oauth_access_token_id'), $bearerToken)) {
            return null;
        }

        return $client;
    }
}