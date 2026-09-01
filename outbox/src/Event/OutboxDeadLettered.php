<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Event;

use Menumbing\Contract\Outbox\OutboxRecord;
use Throwable;

/**
 * Dispatched when an outbox message has exceeded the maximum retry
 * count and will no longer be retried.
 *
 * The message is now in DEAD_LETTER status and requires manual
 * intervention to re-process or discard.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
final class OutboxDeadLettered
{
    public function __construct(
        public readonly OutboxRecord $record,
        public readonly Throwable $throwable,
    ) {
    }
}
