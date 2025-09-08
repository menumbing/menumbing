<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
interface TokenValidatorInterface
{
    public function isTokenRevoked(string $tokenId): bool;
}