<?php

declare(strict_types=1);

namespace Menumbing\Orm\Criteria;

use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class OrderBy implements CriterionInterface
{
    public function __construct(protected readonly string $column, protected readonly string $sortBy = 'asc')
    {
    }

    public function apply(QueryBuilderInterface $query): void
    {
        $query->orderBy($this->column, $this->sortBy);
    }
}
