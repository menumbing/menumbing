<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
interface OAuthAccessTokenInterface
{
    public function getIdentifier(): string;
}