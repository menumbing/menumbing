<?php

declare(strict_types=1);

namespace Menumbing\Orm\Factory;

use Menumbing\Orm\Contract\QueryBuilderFactoryInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;
use Menumbing\Orm\Model;
use Menumbing\Orm\QueryBuilder;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class QueryBuilderFactory implements QueryBuilderFactoryInterface
{
    public function create(string $modelClass): QueryBuilderInterface
    {
        return $this->makeQueryBuilder()->setModel($this->makeModel($modelClass));
    }

    protected function makeQueryBuilder(): QueryBuilderInterface
    {
        return new QueryBuilder();
    }

    /**
     * @template TModel
     *
     * @param  class-string<TModel>  $modelClass
     *
     * @return TModel
     */
    protected function makeModel(string $modelClass): Model
    {
        return new $modelClass();
    }
}
