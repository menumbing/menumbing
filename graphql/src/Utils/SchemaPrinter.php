<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Utils;

use GraphQL\Type\Schema;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class SchemaPrinter extends \GraphQL\Utils\SchemaPrinter
{
    protected static function hasDefaultRootOperationTypes(Schema $schema): bool
    {
        return $schema->getQueryType() && $schema->getQueryType() === $schema->getType('Query')
            && $schema->getMutationType() && $schema->getMutationType() === $schema->getType('Mutation');
    }
}
