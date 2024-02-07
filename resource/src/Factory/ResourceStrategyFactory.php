<?php

declare(strict_types=1);

namespace Menumbing\Resource\Factory;

use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Menumbing\Resource\Strategy\ChainResourceStrategy;
use Menumbing\Resource\Strategy\JsonErrorStrategy;
use Menumbing\Resource\Strategy\JsonResourceStrategy;
use Psr\Container\ContainerInterface;

use function Hyperf\Config\config;
use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ResourceStrategyFactory
{
    public function __invoke(): ResourceStrategyInterface
    {
        $strategies = [];
        $resourceStrategies = config('resource.strategies', [
            JsonErrorStrategy::class,
            JsonResourceStrategy::class,
        ]);

        foreach ($resourceStrategies as $strategy => $option) {
            if (is_string($strategy)) {
                $strategies[] = make($strategy, (array) $option);

                continue;
            }

            $strategies[] = make($option);
        }

        return make(config('resource.default', ChainResourceStrategy::class), compact('strategies'));
    }
}
