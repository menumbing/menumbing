<?php

declare(strict_types=1);

namespace Menumbing\Orm\Cache;

use Hyperf\Cache\Driver\DriverInterface;
use Menumbing\Orm\Contract\CacheHandlerInterface;
use Menumbing\Orm\Model;

use function Hyperf\Tappable\tap;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class CacheHandler implements CacheHandlerInterface
{
    public function __construct(
        protected readonly DriverInterface $cache,
        protected readonly Model $model,
        protected readonly ?int $ttl = null,
    ) {
    }

    public function runSelectInCache(CacheQueryBuilder $queryBuilder, callable $callable): mixed
    {
        $cacheKey = $this->makeKey($queryBuilder);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        return tap($callable(), fn ($result) => $this->cache->set($cacheKey, $result, $this->ttl));
    }

    public function invalidate(): void
    {
        $this->cache->clearPrefix($this->getCachePrefix());
    }

    protected function makeKey(CacheQueryBuilder $queryBuilder): string
    {
        $parts = [
            $queryBuilder->toSql(),
            implode('-', $queryBuilder->getBindings()),
        ];

        $key = sha1(implode('|', $parts));

        return $this->getCachePrefix() . $key;
    }

    protected function getCachePrefix(): string
    {
        return str_replace('\\', '_', $this->model::class) . ':';
    }
}
