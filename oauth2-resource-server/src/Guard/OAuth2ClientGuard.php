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
use Menumbing\OAuth2\Resource\ResourceServerAuthenticator;
use Psr\Container\ContainerInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OAuth2ClientGuard implements GuardInterface
{
    use GuardHelpers;

    protected AccessTokenProviderInterface $accessTokenProvider;

    public function __construct(
        protected RequestInterface            $request,
        protected ResourceServerAuthenticator $authenticator,
        protected ContainerInterface          $container,
        protected ConfigInterface             $config,
        UserProviderInterface                 $provider,
    )
    {
        $this->provider = $provider;
    }

    public function user(): ?AuthenticatableInterface
    {
        // TODO: Implement user() method.
    }

    public function validate(array $credentials = []): bool
    {
        throw new BadMethodCallException();
    }
}