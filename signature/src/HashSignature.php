<?php

declare(strict_types=1);

namespace Menumbing\Signature;

use Menumbing\Contract\Signature\ClaimInterface;
use Menumbing\Contract\Signature\SignatureInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HashSignature implements SignatureInterface
{
    public function __construct(
        public readonly string $token,
        public readonly string $clientId,
        public readonly ClaimInterface $claim,
    ) {
    }

    public function __toString(): string
    {
        return 'HMAC'.strtoupper($this->claim->algo).'='.$this->token;
    }

    public function getHeaders(): array
    {
        return [
            'Request-Id'        => $this->claim->requestId,
            'Client-Id'         => $this->clientId,
            'Request-Timestamp' => $this->claim->getRequestDateTimeString(),
            'Signature'         => (string) $this,
        ];
    }

    public function isValid(ServerRequestInterface $request): bool
    {
        return (string) $this === $this->extractSignature($request);
    }

    public function extractSignature(ServerRequestInterface $request): string
    {
        return $request->header('Signature');
    }
}
