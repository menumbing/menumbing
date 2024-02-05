<?php

declare(strict_types=1);

namespace Menumbing\Orm\Cache;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Query\Grammars\Grammar;
use Hyperf\Database\Query\Processors\Processor;
use Menumbing\Orm\Contract\CacheHandlerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class CacheQueryBuilder extends Builder
{
    protected bool $cacheEnabled = true;

    public function __construct(
        protected CacheHandlerInterface $cacheHandler,
        ConnectionInterface $connection,
        Grammar $grammar = null,
        Processor $processor = null,
    ) {
        parent::__construct($connection, $grammar, $processor);
    }

    public function disableCache(): static
    {
        $this->cacheEnabled = false;

        return $this;
    }

    protected function runSelect(): mixed
    {
        if (!$this->cacheEnabled) {
            return parent::runSelect();
        }

        return $this->cacheHandler->runSelectInCache($this, fn() => parent::runSelect());
    }
}
