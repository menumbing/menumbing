<?php

declare(strict_types=1);

namespace Menumbing\Orm\Criteria;

use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class Latest implements CriterionInterface
{
    public function __construct(protected readonly string $column = 'created_at')
    {
    }

    public function apply(QueryBuilderInterface $query): void
    {
        $query->latest($this->column);
    }
}
