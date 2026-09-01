<?php

declare(strict_types=1);

namespace Menumbing\Contract\Outbox;

/**
 * Represents the lifecycle state of an outbox message.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
enum OutboxStatus: string
{
    /** The message is waiting to be relayed to the stream. */
    case PENDING = 'pending';

    /** The message failed at least once but will be retried. */
    case FAILED = 'failed';

    /** The message has been successfully relayed to the stream. */
    case SENT = 'sent';

    /** The message exceeded the maximum retry count and will no longer be retried. */
    case DEAD_LETTER = 'dead_letter';
}
