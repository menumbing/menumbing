<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Provider\AccessToken;

use Hyperf\Contract\ConfigInterface;
use InvalidArgumentException;
use Menumbing\OAuth2\ResourceServer\Contract\AccessTokenProviderInterface;
use function Hyperf\Support\make;

/**
 * @property ConfigInterface $config
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
trait AccessTokenProviderResolverTrait
{
    protected function resolveAccessTokenProvider(string $name): AccessTokenProviderInterface
    {
        $config = $this->config->get('oauth2_resource_server.access_token.providers.' . $name);

        if (is_null($config)) {
            throw new InvalidArgumentException(
                "OAuth2 access token provider [$name] must be defined."
            );
        }

        $driverClass = $config['driver'] ?? null;
        if (empty($driverClass)) {
            throw new InvalidArgumentException(
                'OAuth2 access token \'driver\' must be defined.'
            );
        }

        return make($driverClass, ['options' => $config['options'] ?? []]);
    }
}