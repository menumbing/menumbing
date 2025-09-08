<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

use Hyperf\Contract\Arrayable;
use HyperfExtension\Auth\GenericUser;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class Client extends GenericUser implements ClientInterface, Arrayable
{
    public function getIdentifier(): string
    {
        return $this->attributes['id'];
    }

    public function getName(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}