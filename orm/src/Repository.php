<?php

declare(strict_types=1);

namespace Menumbing\Orm;

use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Menumbing\Orm\Constant\LockMode;
use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\PersistentInterface;
use Menumbing\Orm\Contract\QueryBuilderFactoryInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;
use Menumbing\Orm\Contract\RepositoryInterface;
use Menumbing\Orm\Criteria\Criteria;
use Menumbing\Orm\Criteria\EagerLoad;
use Menumbing\Orm\Criteria\Lock;

use function Hyperf\Tappable\tap;

/**
 * @template TModel
 *
 * @implements RepositoryInterface<TModel>
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class Repository implements RepositoryInterface
{
    private array $criteria = [];

    final public function __construct(
        protected QueryBuilderFactoryInterface $queryBuilderFactory,
        protected PersistentInterface $persistent,
        protected string $modelClass,
    ) {
    }

    public function withRelations(array $relations): static
    {
        return $this->withCriteria(new EagerLoad($relations));
    }

    public function withCriteria(CriterionInterface|array $criteria): static
    {
        return tap($this, fn() => array_push($this->criteria, $criteria));
    }

    public function withLock(LockMode $lockMode): static
    {
        return $this->withCriteria(new Lock($lockMode));
    }

    /**
     * @param  int|string  $id
     *
     * @return TModel
     */
    public function findById(int|string $id): ?Model
    {
        return $this->newQuery()->findByKey($id);
    }

    /**
     * @param  array  $criteria
     *
     * @return TModel
     */
    public function findOneBy(array $criteria): ?Model
    {
        return $this->newQuery()->withCriteria($this->releaseCriteria(new Criteria($criteria)))->first();
    }

    /**
     * @param  array  $criteria
     *
     * @return Collection<TModel>
     */
    public function findBy(array $criteria): Collection
    {
        return $this->newQuery()->withCriteria($this->releaseCriteria(new Criteria($criteria)))->get();
    }

    /**
     * @return Collection<TModel>
     */
    public function findAll(): Collection
    {
        return $this->newQuery()->withCriteria($this->releaseCriteria())->get();
    }

    /**
     * @param  int  $pageSize
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     *
     * @return LengthAwarePaginatorInterface<TModel>
     */
    public function paginate(int $pageSize, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface
    {
        return $this->newQuery()->withCriteria($this->releaseCriteria())->paginate($pageSize, $columns, $pageName, $page);
    }

    /**
     * @param  Model  $model
     *
     * @return TModel
     */
    public function save(Model $model): Model
    {
        return $this->persistent->save($model);
    }

    /**
     * @param  Model  $model
     *
     * @return TModel
     */
    public function delete(Model $model): Model
    {
        return $this->persistent->delete($model);
    }

    protected function releaseCriteria(array|CriterionInterface $addCriteria = []): array
    {
        $criteria = $this->criteria;
        $this->criteria = [];

        $addCriteria = is_array($addCriteria) ? $addCriteria : [$addCriteria];

        return [...$criteria, ...$addCriteria];
    }

    protected function newQuery(): QueryBuilderInterface
    {
        return $this->queryBuilderFactory->create($this->modelClass);
    }
}
