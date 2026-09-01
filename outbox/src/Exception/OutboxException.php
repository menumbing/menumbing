<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Exception;

use RuntimeException;

/**
 * Base exception for outbox-related errors.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxException extends RuntimeException
{
}
