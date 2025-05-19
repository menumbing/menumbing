<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Contract;

use HyperfExtension\Auth\Authenticatable;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class User implements AuthenticatableInterface
{
    use Authenticatable;

    public function __construct(
        protected string $id,
        protected string $name,
    )
    {
    }

    public function getIdentifier(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKeyName(): string
    {
        return 'id';
    }
}