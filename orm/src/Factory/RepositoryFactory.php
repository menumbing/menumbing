<?php

declare(strict_types=1);

namespace Menumbing\Orm\Factory;

use Hyperf\Di\Annotation\Inject;
use Menumbing\Orm\Contract\PersistentInterface;
use Menumbing\Orm\Contract\QueryBuilderFactoryInterface;
use Menumbing\Orm\Contract\RepositoryFactoryInterface;
use Menumbing\Orm\Contract\RepositoryInterface;

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
        $persistent = $this->persistent;

        return new $repositoryClass($this->queryBuilderFactory, $persistent, $modelClass);
    }
}
