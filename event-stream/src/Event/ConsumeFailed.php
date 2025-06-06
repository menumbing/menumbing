<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Event;

use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ConsumeFailed extends ConsumeEvent
{
    public function __construct(
        string $consumerName,
        string $groupName,
        StreamMessage $message,
        StreamInterface $stream,
        string $streamDriver,
        public readonly \Throwable $exception,
    ) {
        parent::__construct($consumerName, $groupName, $message, $stream, $streamDriver);
    }
}
