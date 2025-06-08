<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class AccessToken implements OAuthAccessTokenInterface
{
    public function __construct(
        protected string  $id,
        protected ?string $token = null,
    )
    {
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }
}