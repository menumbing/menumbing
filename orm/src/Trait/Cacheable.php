<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

use Hyperf\Cache\CacheManager;
use Hyperf\Cache\Driver\DriverInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Query\Builder;
use Menumbing\Orm\Cache\CacheHandler;
use Menumbing\Orm\Cache\CacheQueryBuilder;
use Menumbing\Orm\Contract\CacheableInterface;
use Menumbing\Orm\Contract\CacheHandlerInterface;
use Menumbing\Orm\Model;

use function Hyperf\Config\config;
use function Hyperf\Tappable\tap;

/**
 * @mixin Model
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait Cacheable
{
    protected static bool $cacheEnabled = true;

    private ?CacheHandlerInterface $cacheHandler = null;

    public function isCacheEnabled(): bool
    {
        return $this instanceof CacheableInterface && static::$cacheEnabled && config('orm.cache.enabled');
    }

    public static function disableCache(): void
    {
        static::$cacheEnabled = false;
    }

    public static function enableCache(): void
    {
        static::$cacheEnabled = true;
    }

    public function invalidateCache(): void
    {
        $this->getCacheHandler()->invalidate();
    }

    protected function newBaseQueryBuilder(): Builder
    {
        if ($this->isCacheEnabled()) {
            $connection = $this->getConnection();

            return new CacheQueryBuilder(
                $this->getCacheHandler(),
                $connection,
                $connection->getQueryGrammar(),
                $connection->getPostProcessor()
            );
        }

        return parent::newBaseQueryBuilder();
    }

    protected function getCacheDriver(): DriverInterface
    {
        $container = ApplicationContext::getContainer();
        $manager = $container->get(CacheManager::class);

        return $manager->getDriver(config('orm.cache.store'));
    }

    protected function cacheTTL(): ?int
    {
        return null;
    }

    protected function getCacheHandler(): CacheHandlerInterface
    {
        if (null !== $this->cacheHandler) {
            return $this->cacheHandler;
        }

        $this->cacheHandler = new CacheHandler($this->getCacheDriver(), $this, $this->cacheTTL() ?? config('orm.cache.ttl'));

        return $this->cacheHandler;
    }
}
