<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use GraphQL\Server\ServerConfig;
use GraphQL\Type\Schema;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\Context\Context;
use TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ServerConfigFactory
{
    public function __invoke(ContainerInterface $container): ServerConfig
    {
        $serverConfig = new ServerConfig();
        $serverConfig->setSchema($container->get(Schema::class));
        $serverConfig->setErrorsHandler([WebonyxErrorHandler::class, 'errorHandler']);
        $serverConfig->setErrorFormatter([WebonyxErrorHandler::class, 'errorFormatter']);
        $serverConfig->setContext(new Context());

        return $serverConfig;
    }
}
