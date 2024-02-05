<?php

declare(strict_types=1);

namespace Menumbing\Orm\Factory;

use Hyperf\Di\Annotation\Inject;
use Menumbing\Orm\Contract\PersistentInterface;
use Menumbing\Orm\Contract\QueryBuilderFactoryInterface;
use Menumbing\Orm\Contract\RepositoryFactoryInterface;
use Menumbing\Orm\Contract\RepositoryInterface;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RepositoryFactory implements RepositoryFactoryInterface
{
    #[Inject]
    protected QueryBuilderFactoryInterface $queryBuilderFactory;

    #[Inject]
    protected PersistentInterface $persistent;

    /**
     * @template TModel
     *
     * @param  class-string<TModel>  $modelClass
     * @param  string  $repositoryClass
     *
     * @return RepositoryInterface<TModel>
     */
    public function create(string $modelClass, string $repositoryClass): RepositoryInterface
    {
        $query = $this->queryBuilderFactory->create($modelClass);
        $persistent = $this->persistent;

        return new $repositoryClass($query, $persistent);
    }
}
