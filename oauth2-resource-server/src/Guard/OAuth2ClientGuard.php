<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Guard;

use BadMethodCallException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Contracts\GuardInterface;
use HyperfExtension\Auth\Contracts\UserProviderInterface;
use HyperfExtension\Auth\GuardHelpers;
use Menumbing\OAuth2\Resource\Contract\AccessTokenProviderInterface;
use Menumbing\OAuth2\Resource\OAuth2GuardHelpers;
use Menumbing\OAuth2\Resource\ResourceServerAuthenticator;
use Menumbing\OAuth2\Resource\Util\RequestExtractor;
use Menumbing\OAuth2\Resource\ValidatesScopes;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuth2ClientGuard implements GuardInterface
{
    use GuardHelpers, OAuth2GuardHelpers, ValidatesScopes;

    public function __construct(
        protected RequestInterface            $request,
        protected ResourceServerAuthenticator $authenticator,
        protected ContainerInterface          $container,
        protected ConfigInterface             $config,
        UserProviderInterface                 $provider,
    )
    {
        $this->provider = $provider;

        $this->accessTokenProvider = $this->getAccessTokenProvider(
            $this->config->get('oauth2-resource-server.access_token.provider', 'stateless')
        );
    }

    public function user(): ?AuthenticatableInterface
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if (RequestExtractor::bearerToken($this->request)) {
            $user = $this->authenticateWithBearerToken($this->request);
        } else if ($token = $this->request->cookie($this->config->get('oauth2-server.cookie.name', 'oauth2_token'))) {
            $request = $this->request->withHeader('Authorization', 'Bearer ' . $token);
            $user = $this->authenticateWithBearerToken($request);;
        }

        return $this->user = $user ?? null;
    }

    public function validate(array $credentials = []): bool
    {
        throw new BadMethodCallException();
    }

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

    protected function getAccessTokenProvider(string $name): AccessTokenProviderInterface
    {
        return $this->container->get(
            $this->config->get('oauth2-resource-server.access_token.providers.' . $name),
        );
    }
}