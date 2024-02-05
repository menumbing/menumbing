<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface QueryBuilderFactoryInterface
{
    public function create(string $modelClass): QueryBuilderInterface;
}
