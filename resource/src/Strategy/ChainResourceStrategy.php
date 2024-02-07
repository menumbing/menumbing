<?php

declare(strict_types=1);

namespace Menumbing\Resource\Strategy;

use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Menumbing\Resource\Exception\UnsupportedStrategyException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ChainResourceStrategy implements ResourceStrategyInterface
{
    /**
     * @var ResourceStrategyInterface[]
     */
    protected array $strategies = [];

    public function __construct(array $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->add($strategy);
        }
    }

    public function add(ResourceStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function render(mixed $resource): mixed
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($resource)) {
                return $strategy->render($resource);
            }
        }

        throw new UnsupportedStrategyException('There is no supported strategy for given resource', 500);
    }

    public function supports(mixed $resource): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($resource)) {
                return true;
            }
        }

        return false;
    }
}
