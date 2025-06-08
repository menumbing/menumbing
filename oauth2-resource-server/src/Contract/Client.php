<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

use HyperfExtension\Auth\Authenticatable;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class Client implements OAuthClientInterface, AuthenticatableInterface
{
    use Authenticatable;

    public function __construct(
        protected string  $id,
        protected ?string $name = null,
    )
    {
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getKeyName(): string
    {
        return 'id';
    }
}