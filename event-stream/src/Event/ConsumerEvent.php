<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Event;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class ConsumerEvent
{
    public function __construct(
        public readonly string $consumerName,
        public readonly string $groupName,
        public readonly string $streamName,
        public readonly string $streamDriver,
    ) {
    }
}
