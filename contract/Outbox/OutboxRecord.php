<?php

declare(strict_types=1);

namespace Menumbing\Contract\Outbox;

use Menumbing\Contract\EventStream\StreamMessage;

/**
 * A read-only representation of an outbox message returned by the storage.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxRecord
{
    public function __construct(
        public readonly string $id,
        public readonly StreamMessage $message,
        public readonly OutboxStatus $status,
        public readonly int $retryCount,
        public readonly ?string $lastError = null,
        public readonly ?string $sentAt = null,
        public readonly ?string $failedAt = null,
    ) {
    }
}
