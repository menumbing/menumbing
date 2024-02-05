<?php

declare(strict_types=1);

namespace Menumbing\Orm\Persistent;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Pipeline\Pipeline;
use Menumbing\Orm\Contract\PersistentMiddlewareInterface;
use Menumbing\Orm\Action;
use Menumbing\Orm\Contract\PersistentInterface;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DatabasePersistent implements PersistentInterface
{
    /**
     * @var PersistentMiddlewareInterface[]
     */
    protected array $middlewares = [];

    #[Inject]
    protected ?ContainerInterface $container = null;

    public function __construct(array $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->container?->make($middleware);
            }

            $this->addMiddleware($middleware);
        }
    }

    public function addMiddleware(PersistentMiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function save(Model $model): Model
    {
        return $this->dispatchThroughMiddlewares(Action::saveAction($model), function (Action $action) {
            $action->model->push();

            return $action->model->refresh();
        });
    }

    public function delete(Model $model): Model
    {
        return $this->dispatchThroughMiddlewares(Action::deleteAction($model), function (Action $action) {
            $action->model->delete();

            return $action->model;
        });
    }

    protected function dispatchThroughMiddlewares(Action $action, callable $callback): mixed
    {
        return (new Pipeline($this->container))
            ->send($action)
            ->through($this->middlewares)
            ->then($callback);
    }
}
