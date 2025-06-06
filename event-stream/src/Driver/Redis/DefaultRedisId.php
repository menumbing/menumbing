<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Driver\Redis;

use Menumbing\Contract\EventStream\IdProviderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DefaultRedisId implements IdProviderInterface
{
    public function newId(): string
    {
        return '*';
    }
}
