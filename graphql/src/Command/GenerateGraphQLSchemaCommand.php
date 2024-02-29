<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Command;

use GraphQL\Type\Schema;
use Hyperf\Command\Command;
use Menumbing\GraphQL\Utils\SchemaPrinter;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GenerateGraphQLSchemaCommand extends Command
{
    protected ?string $signature = 'gen:graphql-schema {--O|output= : Output file name. If not specified, prints on stdout}';

    public function __construct(private Schema $schema)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $output = $this->option('output');

        $sdl = SchemaPrinter::doPrint($this->schema, [
            "sortArguments" => true,
            "sortEnumValues" => true,
            "sortFields" => true,
            "sortInputFields" => true,
            "sortTypes" => true,
        ]);

        if ($output === null) {
            $this->line($sdl);
        } else {
            file_put_contents($output, $sdl);
        }

        return static::SUCCESS;
    }
}
