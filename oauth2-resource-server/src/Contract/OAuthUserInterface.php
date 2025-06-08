<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface OAuthUserInterface
{
    public function getIdentifier(): string;

    public function getName(): string;
}
