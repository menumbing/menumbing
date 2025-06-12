<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

use HyperfExtension\Auth\Contracts\GuardInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface OAuth2GuardInterface extends GuardInterface
{
    public function client(): ?OAuthClientInterface;

    public function tokenScopes(): array;
}
