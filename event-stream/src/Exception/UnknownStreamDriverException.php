<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Exception;

use Exception;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class UnknownStreamDriverException extends Exception
{
    public function __construct(string $driverName, int $code = 0, Exception $previous = null)
    {
        parent::__construct(
            sprintf('Driver "%s" is not found. Please make sure config "event_stream.drivers.%s" is exists.', $driverName, $driverName),
            $code,
            $previous
        );
    }
}
