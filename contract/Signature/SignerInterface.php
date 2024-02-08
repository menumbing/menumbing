<?php

declare(strict_types=1);

namespace Menumbing\Contract\Signature;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface SignerInterface
{
    public function sign(string $clientId, string $clientSecret, ClaimInterface $claim): SignatureInterface;
}
