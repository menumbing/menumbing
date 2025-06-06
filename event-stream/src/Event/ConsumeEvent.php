<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Event;

use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class ConsumeEvent
{
    public function __construct(
        public readonly string $consumerName,
        public readonly string $groupName,
        public readonly StreamMessage $message,
        public readonly StreamInterface $stream,
        public readonly string $streamDriver,
    ) {
    }
}
