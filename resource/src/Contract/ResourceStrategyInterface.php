<?php

declare(strict_types=1);

namespace Menumbing\Resource\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ResourceStrategyInterface
{
    public function render(mixed $resource): mixed;

    public function supports(mixed $resource): bool;
}
