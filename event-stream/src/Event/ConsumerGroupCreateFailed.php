<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Event;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ConsumerGroupCreateFailed extends ConsumerEvent
{
    public function __construct(
        string $consumerName,
        string $groupName,
        string $streamName,
        string $streamDriver,
        public readonly \Throwable $exception,
    ) {
        parent::__construct($consumerName, $groupName, $streamName, $streamDriver);
    }
}
