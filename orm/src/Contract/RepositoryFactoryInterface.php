<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface RepositoryFactoryInterface
{
    /**
     * @template TRepo
     *
     * @param  string  $modelClass
     * @param  class-string<TRepo>  $repositoryClass
     *
     * @return TRepo
     */
    public function create(string $modelClass, string $repositoryClass): RepositoryInterface;
}
