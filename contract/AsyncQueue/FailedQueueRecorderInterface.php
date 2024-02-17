<?php

declare(strict_types=1);

namespace Menumbing\Contract\AsyncQueue;

use Generator;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface FailedQueueRecorderInterface
{
    public function record(string $id, string $pool, string $payload, Throwable $exception): void;

    public function all(?string $pool = null): Generator;

    public function count(?string $pool = null): int;

    public function find(string $id): ?object;

    public function forget(string $id): bool;

    public function flush(?string $pool = null): int;
}
