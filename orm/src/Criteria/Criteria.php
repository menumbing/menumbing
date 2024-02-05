<?php

declare(strict_types=1);

namespace Menumbing\Orm\Criteria;

use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class Criteria implements CriterionInterface
{
    public function __construct(protected readonly array $criteria)
    {
    }

    public function apply(QueryBuilderInterface $query): void
    {
        foreach ($this->criteria as $key => $value) {
            $query->where($key, $value);
        }
    }
}
