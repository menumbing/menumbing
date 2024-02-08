<?php

declare(strict_types=1);

namespace Menumbing\Signature;

use Menumbing\Contract\Signature\ClaimInterface;
use Menumbing\Contract\Signature\SignatureInterface;
use Menumbing\Contract\Signature\SignerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HashSigner implements SignerInterface
{
    public function sign(string $clientId, string $clientSecret, ClaimInterface $claim): SignatureInterface
    {
        return new HashSignature(
            $this->hash(
                $clientSecret,
                $this->makeStringComponent($clientId, $claim),
                $claim->algo
            ),
            $clientId,
            $claim
        );
    }

    protected function makeStringComponent(string $clientId, ClaimInterface $claim): string
    {
        return implode('|', [$clientId, ...$claim->toArray()]);
    }

    protected function hash(string $secret, string $text, string $algo): string
    {
        return hash_hmac($algo, $text, $secret);
    }
}
