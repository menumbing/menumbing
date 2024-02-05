<?php

declare(strict_types=1);

namespace Menumbing\Orm\Persistent\Middleware;

use Closure;
use Hyperf\Di\Annotation\Inject;
use Menumbing\Orm\Action;
use Menumbing\Orm\Constant\ActionType;
use Menumbing\Orm\Contract\PersistentMiddlewareInterface;
use Menumbing\Orm\Event\ModelDeleted;
use Menumbing\Orm\Event\ModelEvent;
use Menumbing\Orm\Event\ModelSaved;
use Menumbing\Orm\Model;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Hyperf\Tappable\tap;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class DispatchEvent implements PersistentMiddlewareInterface
{
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function handle(Action $action, Closure $next): mixed
    {
        return tap($next($action), function (Model $model) use ($action) {
            $this->eventDispatcher->dispatch($this->makeEvent($model, $action));
        });
    }

    protected function makeEvent(Model $model, Action $action): ModelEvent
    {
        return match ($action->type) {
            ActionType::SAVE => new ModelSaved($model),
            ActionType::DELETE => new ModelDeleted($model),
        };
    }
}
