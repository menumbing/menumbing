<?php

declare(strict_types=1);

namespace Menumbing\Orm\Criteria;

use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class EagerLoad implements CriterionInterface
{
    public function __construct(protected readonly array $relations)
    {
    }

    public function apply(QueryBuilderInterface $query): void
    {
        $query->with($this->relations);
    }
}
