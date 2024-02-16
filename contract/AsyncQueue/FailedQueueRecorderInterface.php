<?php

declare(strict_types=1);

namespace Menumbing\Contract\AsyncQueue;

use Generator;
use Hyperf\AsyncQueue\MessageInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface FailedQueueRecorderInterface
{
    public function record(MessageInterface $payload, Throwable $exception): void;

    public function all(): Generator;

    public function find(mixed $id): ?object;

    public function forget(mixed $id): bool;

    public function flush(): void;
}
