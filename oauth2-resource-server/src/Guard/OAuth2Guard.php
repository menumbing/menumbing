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
use League\OAuth2\Server\ResourceServer;
use Menumbing\OAuth2\ResourceServer\Bridge\Repository\AccessTokenRepository;
use Menumbing\OAuth2\ResourceServer\Contract\ClientInterface;
use Menumbing\OAuth2\ResourceServer\Contract\TokenValidatorInterface;
use Menumbing\OAuth2\ResourceServer\MakeCryptKey;
use Menumbing\OAuth2\ResourceServer\ResourceServerAuthenticator;
use Menumbing\OAuth2\ResourceServer\Storage\CryptKeyStorage;
use Menumbing\OAuth2\ResourceServer\Util\RequestExtractor;
use Menumbing\OAuth2\ResourceServer\ValidatesScopes;
use Psr\Http\Message\ServerRequestInterface;
use function Hyperf\Support\make;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuth2Guard implements GuardInterface
{
    use GuardHelpers,
        ValidatesScopes,
        MakeCryptKey;

    protected ResourceServerAuthenticator $authenticator;
    protected TokenValidatorInterface $tokenValidator;
    protected ?ClientInterface $client = null;
    protected array $scopes = [];

    public function __construct(
        protected RequestInterface $request,
        protected ConfigInterface  $config,
        protected CryptKeyStorage  $cryptKeyStorage,
        UserProviderInterface      $provider,
        array                      $options = [],
    )
    {
        $this->provider = $provider;
        $this->tokenValidator = $this->makeTokenValidator($options['token_validator'] ?? 'stateless');

        $this->authenticator = new ResourceServerAuthenticator(
            new ResourceServer(
                new AccessTokenRepository($this->tokenValidator),
                $this->cryptKeyStorage->getPublicKey(),
            ),
        );
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

    public function validate(array $credentials = []): bool
    {
        throw new BadMethodCallException();
    }

    protected function authenticateWithBearerToken(ServerRequestInterface $request): ?AuthenticatableInterface
    {
        $bearerToken = RequestExtractor::bearerToken($request);
        $request = $this->authenticator->authenticateRequest($request);

        $this->validateScopes($request);

        return $this->provider->retrieveByToken(
            identifier: $request->getAttribute('oauth_user_id'),
            token     : $bearerToken,
        );
    }

    protected function makeTokenValidator(string $name): TokenValidatorInterface
    {
        $config = $this->config->get('oauth2_resource_server.token_validators.' . $name);

        if (is_null($config)) {
            throw new InvalidArgumentException(
                "OAuth2 Token Validator [$name] must be defined."
            );
        }

        $driverClass = $config['driver'] ?? null;
        if (empty($driverClass)) {
            throw new InvalidArgumentException(
                'OAuth2 Token Validator \'driver\' must be defined.'
            );
        }

        return make($driverClass, ['options' => $config['options'] ?? []]);
    }
}