<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer;

use Exception;
use Hyperf\HttpServer\Router\Dispatched;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Menumbing\OAuth2\ResourceServer\Exception\AuthenticationException;
use Menumbing\OAuth2\ResourceServer\Exception\MissingScopeException;
use Menumbing\OAuth2\ResourceServer\Util\RequestOptionExtractor;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait ValidatesScopes
{
    protected function validateScopes(ServerRequestInterface $request): ServerRequestInterface
    {
        $anyScopes = (array) RequestOptionExtractor::getOption($request, 'scope', []);
        $allScopes = (array) RequestOptionExtractor::getOption($request, 'scopes', []);
        $tokenScopes = $request->getAttribute('oauth_scopes') ?? [];

        if (empty($anyScopes) && empty($allScopes)) {
            return $request;
        }

        if (in_array('*', $tokenScopes, true)) {
            return $request;
        }

        if (!empty($anyScopes)) {
            $this->validateHasAnyScopes($tokenScopes, $anyScopes);
        } else if (!empty($allScopes)) {
            $this->validateMatchingAllScopes($tokenScopes, $anyScopes);
        }

        return $request;
    }

    protected function validateMatchingAllScopes(array $tokenScopes, array $scopes): void
    {
        $tokenScopes = array_flip($tokenScopes);

        foreach ($scopes as $scope) {
            if (!array_key_exists($scope, $tokenScopes)) {
                throw new MissingScopeException($scopes);
            }
        }
    }

    protected function validateHasAnyScopes(array $tokenScopes, array $scopes): void
    {
        $tokenScopes = array_flip($tokenScopes);

        foreach ($scopes as $scope) {
            if (array_key_exists($scope, $tokenScopes)) {
                return;
            }
        }

        throw new MissingScopeException($scopes);
    }
}
