<?php

declare(strict_types=1);

namespace Menumbing\Contract\Signature;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ClaimInterface
{
    public function refresh(): static;

    public function refreshRequestId(): static;

    public function refreshRequestDataTime(): static;

    public function getRequestDateTimeString(): string;

    public function getDigest(): ?string;

    public function toArray(): array;
}
