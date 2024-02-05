<?php

declare(strict_types=1);

namespace Menumbing\Orm;

use Generator;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\PaginatorInterface;
use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\Database\Model\Collection as ModelCollection;
use Hyperf\Database\Query\Builder;
use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

use function Hyperf\Tappable\tap;

/**
 * @template TModel
 *
 * @implements QueryBuilderInterface<TModel>
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class QueryBuilder implements QueryBuilderInterface
{
    protected Builder|ModelBuilder $queryBuilder;

    protected ?Model $model = null;

    public function setModel(Model $model): static
    {
        return tap($this, function () use ($model) {
            $this->model = $model;
            $this->queryBuilder = $model->newQuery();
        });
    }

    public function withCriteria(array|CriterionInterface $criteria): static
    {
        if (is_array($criteria)) {
            foreach ($criteria as $criterion) {
                $this->withCriteria($criterion);
            }

            return $this;
        }

        $criteria->apply($this);

        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function get(array $columns = ['*']): Collection|ModelCollection
    {
        return $this->runQuery(fn() => $this->queryBuilder->get($columns));
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface
    {
        return $this->runQuery(fn() => $this->queryBuilder->paginate($perPage, $columns, $pageName, $page));
    }

    public function simplePaginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): PaginatorInterface
    {
        return $this->runQuery(fn() => $this->queryBuilder->simplePaginate($perPage, $columns, $pageName, $page));
    }

    public function cursor(): Generator
    {
        $qb = clone $this->queryBuilder;

        $this->refresh();

        yield from $qb->cursor();
    }

    public function findByKey(int|string $key): ?Model
    {
        return tap($this->queryBuilder->whereKey($key)->first(), fn() => $this->refresh());
    }

    public function first(array $columns = ['*']): ?Model
    {
        return $this->runQuery(fn() => $this->queryBuilder->first($columns));
    }

    public function chunk(int $count, callable $callback): bool
    {
        return tap($this->queryBuilder->chunk($count, $callback), fn() => $this->refresh());
    }

    public function count(string|array $columns = '*'): int
    {
        return (int) $this->aggregate(__FUNCTION__, Arr::wrap($columns));
    }

    public function min(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function sum(string $column): int|float
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    public function average(string $column): mixed
    {
        return $this->avg($column);
    }

    public function aggregate(string $function, array|string $columns = ['*']): mixed
    {
        return $this->runQuery(fn() => $this->queryBuilder->getQuery()->aggregate($function, $columns));
    }

    public function __call(string $method, array $args): mixed
    {
        $result = $this->queryBuilder->{$method}(...$args);

        if ($result instanceof Builder || $result instanceof ModelBuilder) {
            return $this;
        }

        return $result;
    }

    protected function runQuery(callable $callable): mixed
    {
        return tap($callable(), fn() => $this->refresh());
    }

    protected function refresh(): void
    {
        $this->setModel($this->model);
    }
}
