<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

use Menumbing\Orm\Cache\CacheQueryBuilder;
use Menumbing\Orm\QueryBuilder;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface CacheHandlerInterface
{
    public function runSelectInCache(CacheQueryBuilder $queryBuilder, callable $callable): mixed;

    public function invalidate(): void;
}
