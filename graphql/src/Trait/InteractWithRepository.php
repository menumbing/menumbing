<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Trait;

use GraphQL\Type\Definition\ResolveInfo;
use Hyperf\Collection\Arr;
use Menumbing\GraphQL\Input\CriteriaInput;
use Menumbing\Orm\Contract\RepositoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait InteractWithRepository
{
    /**
     * @template TRepository
     *
     * @param  ResolveInfo  $resolveInfo
     * @param  TRepository  $repository
     * @param  int  $deepSelection
     * @param  array  $extra
     *
     * @return TRepository
     */
    protected function mount(ResolveInfo $resolveInfo, RepositoryInterface $repository, int $deepSelection = 5, array $extra = []): RepositoryInterface
    {
        $this->loadRelations($resolveInfo->getFieldSelection($deepSelection), $repository);

        foreach (array_filter($extra) as $item) {
            if ($item instanceof CriteriaInput) {
                $item->apply($repository);
            }
        }

        return $repository;
    }

    protected function loadRelations(array $fields, RepositoryInterface $repository): void
    {
        $fields = array_keys(Arr::dot($fields));
        $loadRelations = [];

        foreach ($this->relations() as $key => $relation) {
            $key = is_int($key) ? $relation : $key;

            foreach ($fields as $field) {
                if (str_starts_with($field, $key)) {
                    $loadRelations[$relation] = true;
                }
            }
        }

        $loadRelations = array_keys($loadRelations);

        $repository->withRelations($loadRelations);
    }

    protected function relations(): array
    {
        return [];
    }
}
