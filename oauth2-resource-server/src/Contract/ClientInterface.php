<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ClientInterface
{
    public function getIdentifier(): string;
    public function getName(): ?string;
}
