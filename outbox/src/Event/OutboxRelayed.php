<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Event;

use Menumbing\Contract\Outbox\OutboxRecord;

/**
 * Dispatched when an outbox message has been successfully relayed
 * to the event stream.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
final class OutboxRelayed
{
    public function __construct(
        public readonly OutboxRecord $record,
    ) {
    }
}
