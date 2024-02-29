<?php

declare(strict_types=1);

namespace Menumbing\GraphQL;

use Hyperf\HttpServer\Server;
use Menumbing\GraphQL\Exception\Handler\GraphQLExceptionHandler;
use Menumbing\GraphQL\Middleware\GraphQLMiddleware;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GraphQLServer extends Server
{
    protected function initOption(): void
    {
        $this->middlewares = [...$this->middlewares, GraphQLMiddleware::class];

        parent::initOption();
    }

    protected function getDefaultExceptionHandler(): array
    {
        return [
            GraphQLExceptionHandler::class,
        ];
    }
}
