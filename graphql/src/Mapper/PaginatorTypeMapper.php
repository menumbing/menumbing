<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Mapper;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\PaginatorInterface;
use Menumbing\GraphQL\Exception\PaginatorMissingParameterException;
use RuntimeException;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterface;
use TheCodingMachine\GraphQLite\Types\MutableInterfaceType;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;
use TheCodingMachine\GraphQLite\Types\ResolvableMutableInputInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PaginatorTypeMapper implements TypeMapperInterface
{
    /**
     * @var array<string, MutableInterface&(MutableObjectType|MutableInterfaceType)>
     */
    protected array $cache = [];

    public function __construct(protected RecursiveTypeMapperInterface $recursiveTypeMapper)
    {
    }

    public function canMapClassToType(string $className): bool
    {
        return is_a($className, PaginatorInterface::class, true);
    }

    public function mapClassToType(string $className, ?OutputType $subType): MutableInterface
    {
        if (!$this->canMapClassToType($className)) {
            throw CannotMapTypeException::createForType($className);
        }

        if (null === $subType) {
            throw PaginatorMissingParameterException::noSubType();
        }

        return $this->getObjectType(is_a($className, LengthAwarePaginatorInterface::class, true), $subType);
    }

    protected function getObjectType(bool $countable, OutputType $subType): MutableInterface
    {
        if (! isset($subType->name)) {
            throw new RuntimeException('Cannot get name property from sub type ' . get_class($subType));
        }

        $name = $subType->name;

        $typeName = 'PaginatorResult_' . $name;

        if ($subType instanceof NullableType) {
            $subType = Type::nonNull($subType);
        }

        if (! isset($this->cache[$typeName])) {
            $this->cache[$typeName] = new MutableObjectType([
                'name' => $typeName,
                'fields' => static function () use ($subType, $countable) {
                    $fields = [
                        'items' => [
                            'type' => Type::nonNull(Type::listOf($subType)),
                            'resolve' => static function (PaginatorInterface $root) {
                                return $root->items();
                            },
                        ],
                        'firstItem' => [
                            'type' => Type::int(),
                            'description' => 'Get the "index" of the first item being paginated.',
                            'resolve' => static function (PaginatorInterface $root): int {
                                return $root->firstItem();
                            },
                        ],
                        'lastItem' => [
                            'type' => Type::int(),
                            'description' => 'Get the "index" of the last item being paginated.',
                            'resolve' => static function (PaginatorInterface $root): int {
                                return $root->lastItem();
                            },
                        ],
                        'hasMorePages' => [
                            'type' => Type::boolean(),
                            'description' => 'Determine if there are more items in the data source.',
                            'resolve' => static function (PaginatorInterface $root): bool {
                                return $root->hasMorePages();
                            },
                        ],
                        'perPage' => [
                            'type' => Type::int(),
                            'description' => 'Get the number of items shown per page.',
                            'resolve' => static function (PaginatorInterface $root): int {
                                return $root->perPage();
                            },
                        ],
                        'hasPages' => [
                            'type' => Type::boolean(),
                            'description' => 'Determine if there are enough items to split into multiple pages.',
                            'resolve' => static function (PaginatorInterface $root): bool {
                                return $root->hasPages();
                            },
                        ],
                        'currentPage' => [
                            'type' => Type::int(),
                            'description' => 'Determine the current page being paginated.',
                            'resolve' => static function (PaginatorInterface $root): int {
                                return $root->currentPage();
                            },
                        ],
                        'isEmpty' => [
                            'type' => Type::boolean(),
                            'description' => 'Determine if the list of items is empty or not.',
                            'resolve' => static function (PaginatorInterface $root): bool {
                                return $root->isEmpty();
                            },
                        ],
                        'isNotEmpty' => [
                            'type' => Type::boolean(),
                            'description' => 'Determine if the list of items is not empty.',
                            'resolve' => static function (PaginatorInterface $root): bool {
                                return $root->isNotEmpty();
                            },
                        ],
                    ];

                    if ($countable) {
                        $fields['totalCount'] = [
                            'type' => Type::int(),
                            'description' => 'The total count of items.',
                            'resolve' => static function (LengthAwarePaginatorInterface $root): int {
                                return $root->total();
                            }];
                        $fields['lastPage'] = [
                            'type' => Type::int(),
                            'description' => 'Get the page number of the last available page.',
                            'resolve' => static function (LengthAwarePaginatorInterface $root): int {
                                return $root->lastPage();
                            }];
                    }

                    return $fields;
                },
            ]);
        }

        return $this->cache[$typeName];
    }

    public function canMapNameToType(string $typeName): bool
    {
        return str_starts_with($typeName, 'PaginatorResult_') || str_starts_with($typeName, 'LengthAwarePaginatorResult_');
    }

    public function mapNameToType(string $typeName): Type&NamedType
    {
        if (str_starts_with($typeName, 'LengthAwarePaginatorResult_')) {
            $subTypeName = substr($typeName, 27);
            $lengthAware = true;
        } elseif (str_starts_with($typeName, 'PaginatorResult_')) {
            $subTypeName = substr($typeName, 16);
            $lengthAware = false;
        } else {
            throw CannotMapTypeException::createForName($typeName);
        }

        $subType = $this->recursiveTypeMapper->mapNameToType($subTypeName);

        if (! $subType instanceof OutputType) {
            throw CannotMapTypeException::mustBeOutputType($subTypeName);
        }

        return $this->getObjectType($lengthAware, $subType);
    }

    public function getSupportedClasses(): array
    {
        return [];
    }

    public function canMapClassToInputType(string $className): bool
    {
        return false;
    }

    public function mapClassToInputType(string $className): ResolvableMutableInputInterface
    {
        throw CannotMapTypeException::createForInputType($className);
    }

    public function canExtendTypeForClass(string $className, MutableInterface $type): bool
    {
        return false;
    }

    public function extendTypeForClass(string $className, MutableInterface $type): void
    {
        throw CannotMapTypeException::createForExtendType($className, $type);
    }

    public function canExtendTypeForName(string $typeName, MutableInterface $type): bool
    {
        return false;
    }

    public function extendTypeForName(string $typeName, MutableInterface $type): void
    {
        throw CannotMapTypeException::createForExtendName($typeName, $type);
    }

    public function canDecorateInputTypeForName(string $typeName, ResolvableMutableInputInterface $type): bool
    {
        return false;
    }

    public function decorateInputTypeForName(string $typeName, ResolvableMutableInputInterface $type): void
    {
        throw CannotMapTypeException::createForDecorateName($typeName, $type);
    }
}
