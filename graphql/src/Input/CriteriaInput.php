<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Input;

use Menumbing\GraphQL\Utils\InputFieldsExtractor;
use Menumbing\Orm\Contract\CriterionInterface;
use Menumbing\Orm\Contract\RepositoryInterface;
use Menumbing\Orm\Criteria\Criteria;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class CriteriaInput
{
    public function apply(RepositoryInterface $repository): void
    {
        $values = $this->wrap(InputFieldsExtractor::extractValues($this));

        foreach ($this->criteria() as $criterion) {
            if (is_string($criterion)) {
                $repository->withCriteria(make($criterion, $values));

                continue;
            }

            if (is_callable($criterion)) {
                $repository->withCriteria($criterion($values));

                continue;
            }

            if ($criterion instanceof CriterionInterface) {
                $repository->withCriteria($criterion);
            }
        }
    }

    protected function wrap(array $values): array
    {
        return [
            'criteria' => $values,
        ];
    }

    /**
     * @return array<int, string|callable>
     */
    protected function criteria(): array
    {
        return [
            Criteria::class,
        ];
    }
}
