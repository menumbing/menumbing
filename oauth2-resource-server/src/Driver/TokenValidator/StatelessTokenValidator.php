<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Driver\TokenValidator;

use Menumbing\OAuth2\ResourceServer\Contract\TokenValidatorInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class StatelessTokenValidator implements TokenValidatorInterface
{
    public function isTokenRevoked(string $tokenId): bool
    {
        return false;
    }
}