<?php

declare(strict_types=1);

namespace Menumbing\Signature;

use DateTime;
use DateTimeZone;
use Menumbing\Contract\Signature\ClaimInterface;
use Ramsey\Uuid\Uuid;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Claim implements ClaimInterface
{
    public readonly string $requestId;
    public readonly DateTime $requestDateTime;

    public function __construct(
        public readonly string $targetPath,
        public readonly ?string $body = null,
        public readonly string $algo = 'sha256',
        ?string $requestId = null,
        ?DateTime $requestDateTime = null,
    ) {
        $this->requestId = $requestId ?? $this->generateRequestId();
        $this->requestDateTime = $requestDateTime ?? $this->generateDateTime();

    }

    public function refresh(): static
    {
        return $this->refreshRequestId()->refreshRequestDataTime();
    }

    public function refreshRequestId(): static
    {
        return new static(
            $this->targetPath,
            $this->body,
            $this->algo,
            $this->generateRequestId(),
            $this->requestDateTime,
        );
    }

    public function refreshRequestDataTime(): static
    {
        return new static(
            $this->targetPath,
            $this->body,
            $this->algo,
            $this->requestId,
            $this->generateDateTime(),
        );
    }

    public function getRequestDateTimeString(): string
    {
        $date = $this->requestDateTime->format('c');

        return substr($date, 0, strpos($date, '+')) . 'Z';
    }

    public function getDigest(): ?string
    {
        if (null === $this->body) {
            return null;
        }

        return base64_encode(hash($this->algo, $this->body, true));
    }

    public function toArray(): array
    {
        return array_filter([
            $this->requestId,
            $this->getRequestDateTimeString(),
            $this->targetPath,
            $this->getDigest(),
        ]);
    }

    protected function generateRequestId(): string
    {
        return (string) Uuid::uuid7();
    }

    protected function generateDateTime(): DateTime
    {
        return new DateTime('now', new DateTimeZone('UTC'));
    }
}
