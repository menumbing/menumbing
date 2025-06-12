<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Guard;

use BadMethodCallException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Contracts\GuardInterface;
use HyperfExtension\Auth\Contracts\UserProviderInterface;
use HyperfExtension\Auth\GuardHelpers;
use InvalidArgumentException;
use Menumbing\OAuth2\ResourceServer\Contract\ClientProviderInterface;
use Menumbing\OAuth2\ResourceServer\OAuth2GuardHelpers;
use Menumbing\OAuth2\ResourceServer\Provider\AccessToken\AccessTokenProviderResolverTrait;
use Menumbing\OAuth2\ResourceServer\ResourceServerAuthenticator;
use Menumbing\OAuth2\ResourceServer\Util\RequestExtractor;
use Menumbing\OAuth2\ResourceServer\ValidatesScopes;
use Psr\Http\Message\ServerRequestInterface;
use function Hyperf\Support\make;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
abstract class AbstractOAuth2Guard implements GuardInterface
{
    use GuardHelpers,
        OAuth2GuardHelpers,
        ValidatesScopes,
        AccessTokenProviderResolverTrait;

    public function __construct(
        protected RequestInterface            $request,
        protected ResourceServerAuthenticator $authenticator,
        protected ConfigInterface             $config,
        UserProviderInterface                 $provider,
        array                                 $options = [],
    )
    {
        $this->provider = $provider;

        $this->clientProvider = $this->resolveClientProvider($options['client_provider'] ?? 'stateless');

        $this->accessTokenProvider = $this->resolveAccessTokenProvider($options['access_token_provider'] ?? 'stateless');
    }

    public function user(): ?AuthenticatableInterface
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if (RequestExtractor::bearerToken($this->request)) {
            $user = $this->authenticateWithBearerToken($this->request);
        } else if ($token = $this->request->cookie($this->config->get('oauth2_resource_server.cookie.name', 'oauth2_token'))) {
            $request = $this->request->withHeader('Authorization', 'Bearer ' . $token);
            $user = $this->authenticateWithBearerToken($request);;
        }

        return $this->user = $user ?? null;
    }

    protected function resolveClientProvider(string $name): ClientProviderInterface
    {
        $config = $this->config->get('oauth2_resource_server.client.providers.' . $name);

        if (is_null($config)) {
            throw new InvalidArgumentException(
                "OAuth2 client provider [$name] must be defined."
            );
        }

        $driverClass = $config['driver'] ?? null;
        if (empty($driverClass)) {
            throw new InvalidArgumentException(
                'OAuth2 client provider \'driver\' must be defined.'
            );
        }

        return make($driverClass, ['options' => $config['options'] ?? []]);
    }

    public function validate(array $credentials = []): bool
    {
        throw new BadMethodCallException();
    }

    abstract protected function authenticateWithBearerToken(ServerRequestInterface $request): ?AuthenticatableInterface;
}