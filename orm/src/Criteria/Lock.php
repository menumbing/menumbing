<?php

declare(strict_types=1);

namespace Menumbing\Orm\Criteria;

use Menumbing\Orm\Constant\LockMode;
use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\QueryBuilderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class Lock implements CriterionInterface
{
    public function __construct(protected readonly LockMode $lockMode)
    {
    }

    public function apply(QueryBuilderInterface $query): void
    {
        match ($this->lockMode) {
            LockMode::PESSIMISTIC_READ => $query->sharedLock(),
            LockMode::PESSIMISTIC_WRITE => $query->lockForUpdate(),
        };
    }
}
