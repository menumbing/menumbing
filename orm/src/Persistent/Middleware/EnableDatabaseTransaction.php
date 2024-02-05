<?php

declare(strict_types=1);

namespace Menumbing\Orm\Persistent\Middleware;

use Closure;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Di\Annotation\Inject;
use Menumbing\Orm\Action;
use Menumbing\Orm\Contract\PersistentMiddlewareInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class EnableDatabaseTransaction implements PersistentMiddlewareInterface
{
    #[Inject]
    protected ConnectionResolverInterface $connectionResolver;

    public function handle(Action $action, Closure $next): mixed
    {
        return $this->connectionResolver->connection()->transaction(fn() => $next($action));
    }
}
