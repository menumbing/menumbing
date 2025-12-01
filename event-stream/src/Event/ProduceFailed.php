<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Event;

use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ProduceFailed
{
    public function __construct(
        public readonly Throwable $throwable,
        public readonly StreamMessage $message,
        public readonly StreamInterface $stream,
        public readonly string $streamDriver,
        public readonly float $startTime,
        public readonly ?float $endTime = null,
    ) {
    }
}
