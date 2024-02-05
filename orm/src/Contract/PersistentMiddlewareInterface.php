<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

use Closure;
use Menumbing\Orm\Action;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface PersistentMiddlewareInterface
{
    public function handle(Action $action, Closure $next): mixed;
}
