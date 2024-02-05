<?php

declare(strict_types=1);

namespace Menumbing\Orm;

use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Menumbing\Orm\Constant\LockMode;
use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\PersistentInterface;
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
    protected Model $model;
    protected QueryBuilderInterface $query;
    protected PersistentInterface $persistent;

    public function __construct(
        QueryBuilderInterface $query,
        PersistentInterface $persistent,
    ) {
        $this->query = $query;
        $this->persistent = $persistent;
        $this->model = $this->query->getModel();
    }

    public function withRelations(array $relations): static
    {
        return $this->withCriteria(new EagerLoad($relations));
    }

    public function withCriteria(CriterionInterface|array $criteria): static
    {
        return tap($this, fn() => $this->query->withCriteria($criteria));
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
        return $this->query->findByKey($id);
    }

    /**
     * @param  array  $criteria
     *
     * @return TModel
     */
    public function findOneBy(array $criteria): ?Model
    {
        return $this->query->withCriteria(new Criteria($criteria))->first();
    }

    /**
     * @param  array  $criteria
     *
     * @return Collection<TModel>
     */
    public function findBy(array $criteria): Collection
    {
        return $this->query->withCriteria(new Criteria($criteria))->get();
    }

    /**
     * @return Collection<TModel>
     */
    public function findAll(): Collection
    {
        return $this->query->get();
    }

    /**
     * @param  int  $pageSize
     *
     * @return LengthAwarePaginatorInterface<TModel>
     */
    public function paginate(int $pageSize): LengthAwarePaginatorInterface
    {
        return $this->query->paginate($pageSize);
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
}
