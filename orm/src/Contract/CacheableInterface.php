<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface CacheableInterface
{
    public static function disableCache(): void;

    public static function enableCache(): void;

    public function isCacheEnabled(): bool;

    public function invalidateCache(): void;
}
