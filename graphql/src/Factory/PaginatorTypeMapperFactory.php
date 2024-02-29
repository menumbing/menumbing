<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use Menumbing\GraphQL\Mapper\PaginatorTypeMapper;
use TheCodingMachine\GraphQLite\FactoryContext;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\TypeMapperInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PaginatorTypeMapperFactory implements TypeMapperFactoryInterface
{
    public function create(FactoryContext $context): TypeMapperInterface
    {
        return new PaginatorTypeMapper($context->getRecursiveTypeMapper());
    }
}
