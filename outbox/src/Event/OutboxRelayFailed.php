<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Event;

use Menumbing\Contract\Outbox\OutboxRecord;
use Throwable;

/**
 * Dispatched when relaying an outbox message to the stream failed,
 * but the message has not yet exceeded the maximum retry count.
 *
 * The message will be retried after the configured retry delay.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
final class OutboxRelayFailed
{
    public function __construct(
        public readonly OutboxRecord $record,
        public readonly Throwable $throwable,
    ) {
    }
}
