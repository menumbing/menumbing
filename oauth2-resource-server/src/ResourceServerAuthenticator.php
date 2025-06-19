<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer;

use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Menumbing\OAuth2\ResourceServer\Exception\AuthenticationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class ResourceServerAuthenticator
{
    protected ?string $clientId = null;

    protected ?string $userId = null;

    protected array $tokenScopes = [];

    public function __construct(protected ResourceServer $server)
    {
    }

    public function authenticateRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        try {
            $request = $this->server->validateAuthenticatedRequest($request);

            $this->clientId = $request->getAttribute('oauth_client_id');
            $this->userId = $request->getAttribute('oauth_user_id');
            $this->tokenScopes = $request->getAttribute('oauth_scopes') ?? [];

            return $request;
        } catch (OAuthServerException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new AuthenticationException(sprintf('Unauthorized: %s', $e->getMessage()), $e->getCode());
        }
    }
}