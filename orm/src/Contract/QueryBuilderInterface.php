<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

use Generator;
use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\PaginatorInterface;
use Hyperf\Database\Model\Collection as ModelCollection;
use Menumbing\Orm\Model;

/**
 * @template TModel
 *
 * @mixin Model
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface QueryBuilderInterface
{
    public function setModel(Model $model): static;

    public function withCriteria(CriterionInterface|array $criteria): static;

    /**
     * @return TModel
     */
    public function getModel(): Model;

    /**
     * @param  array  $columns
     *
     * @return Collection<TModel>|ModelCollection<TModel>
     */
    public function get(array $columns = ['*']): Collection|ModelCollection;

    /**
     * Paginate the given query by a specific number of items per page.
     *
     * @param  int  $perPage  The number of items per page (default: 15).
     * @param  array  $columns  The columns to retrieve (default: ['*']).
     * @param  string  $pageName  The query string variable used to store the page number (default: 'page').
     * @param  int|null  $page  The current page number (default: null).
     *
     * @return LengthAwarePaginatorInterface<TModel>   An instance of LengthAwarePaginatorInterface representing the paginated collection.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface;

    public function simplePaginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): PaginatorInterface;

    /**
     * @return Generator<TModel>
     */
    public function cursor(): Generator;

    /**
     * @param  int|string  $key
     *
     * @return TModel|null
     */
    public function findByKey(int|string $key): ?Model;

    /**
     * @param  array  $columns
     *
     * @return TModel|null
     */
    public function first(array $columns = ['*']): ?Model;

    public function chunk(int $count, callable $callback): bool;

    public function count(string|array $columns = '*'): int;

    public function min(string $column): mixed;

    public function max(string $column): mixed;

    public function sum(string $column): int|float;

    public function avg(string $column): mixed;

    public function average(string $column): mixed;

    public function aggregate(string $function, string|array $columns = ['*']): mixed;
}
