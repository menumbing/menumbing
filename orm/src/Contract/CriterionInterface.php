<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface CriterionInterface
{
    public function apply(QueryBuilderInterface $query): void;
}
