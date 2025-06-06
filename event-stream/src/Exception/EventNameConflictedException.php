<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Exception;

use Exception;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EventNameConflictedException extends Exception
{
    public function __construct(string $eventName, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Event name "%s" is conflicted', $eventName), $code, $previous);
    }
}
