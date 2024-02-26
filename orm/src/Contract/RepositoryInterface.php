<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

use Hyperf\Collection\Collection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Menumbing\Orm\Constant\LockMode;
use Menumbing\Orm\Model;

/**
 * @template TModel
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface RepositoryInterface
{
    /**
     * Add eager load relations.
     *
     * @param  array  $relations
     *
     * @return static
     */
    public function withRelations(array $relations): static;

    /**
     * Add criteria.
     */
    public function withCriteria(CriterionInterface|array $criteria): static;

    /**
     * Add database lock.
     */
    public function withLock(LockMode $lockMode): static;

    /**
     * Find one model by id.
     *
     * @param  string|int  $id
     *
     * @return TModel|null
     */
    public function findById(string|int $id): ?Model;

    /**
     * Find one model by criteria.
     *
     * @param  array  $criteria
     *
     * @return TModel|null
     */
    public function findOneBy(array $criteria): ?Model;

    /**
     * Find collection of models by criteria.
     *
     * @param  array  $criteria
     *
     * @return Collection<TModel>
     */
    public function findBy(array $criteria): Collection;

    /**
     * Find collection of models.
     *
     * @return Collection<TModel>
     */
    public function findAll(): Collection;

    /**
     * Paginate models.
     *
     * @param  int  $pageSize
     * @param  array  $columns
     * @param  string $pageName
     * @param  int|null $page
     *
     * @return LengthAwarePaginatorInterface
     */
    public function paginate(int $pageSize, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginatorInterface;

    /**
     * Save the model.
     *
     * @param  TModel  $model
     *
     * @return TModel
     */
    public function save(Model $model): Model;

    /**
     * Delete the model.
     *
     * @param  TModel  $model
     *
     * @return TModel
     */
    public function delete(Model $model): Model;
}
