<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Database\Resilience\Stubs;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * ConnectionFactory only reaches for the container when it builds a connector, which none of the
 * createConnection() paths do, so an empty container is enough to instantiate the factory.
 */
class EmptyContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        throw new RuntimeException(sprintf('Service "%s" is not available in the test container.', $id));
    }

    public function has(string $id): bool
    {
        return false;
    }
}
