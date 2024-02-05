<?php

declare(strict_types=1);

namespace Menumbing\Orm\Persistent\Middleware;

use Closure;
use Hyperf\Di\Annotation\Inject;
use Menumbing\Orm\Action;
use Menumbing\Orm\Contract\HasDomainEventInterface;
use Menumbing\Orm\Contract\PersistentMiddlewareInterface;
use Menumbing\Orm\Model;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Hyperf\Tappable\tap;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class ReleasePendingDomainEvent implements PersistentMiddlewareInterface
{
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function handle(Action $action, Closure $next): mixed
    {
        return tap($next($action), function (Model $model) {
            $this->releasePendingDomainEvents($model);
        });
    }

    protected function releasePendingDomainEvents(Model $model): void
    {
        if ($model instanceof HasDomainEventInterface) {
            $events = $model->releasePendingDomainEvents();
            foreach ($events as $event) {
                $this->eventDispatcher->dispatch($event);
            }
        }

        foreach ($model->getRelations() as $models) {
            $models = is_iterable($models) ? $models : [$models];

            foreach ($models as $model) {
                if ($model) {
                    $this->releasePendingDomainEvents($model);
                }
            }
        }
    }
}
