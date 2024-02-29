<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Controller;

use GraphQL\Type\Schema;
use Menumbing\GraphQL\Utils\SchemaPrinter;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PrintSchemaController
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function print(): string
    {
        $schema = $this->container->get(Schema::class);

        return SchemaPrinter::doPrint($schema, [
            "sortArguments" => true,
            "sortEnumValues" => true,
            "sortFields" => true,
            "sortInputFields" => true,
            "sortTypes" => true,
        ]);
    }
}
