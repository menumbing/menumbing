<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Menumbing\GraphQL;

use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Schema;
use Menumbing\GraphQL\Command\GenerateGraphQLSchemaCommand;
use Menumbing\GraphQL\Factory\AuthenticationServiceFactory;
use Menumbing\GraphQL\Factory\SchemaFactory;
use Menumbing\GraphQL\Factory\ServerConfigFactory;
use Menumbing\GraphQL\Factory\StandardServerFactory;
use Menumbing\GraphQL\Listener\BootGraphQLListener;
use Menumbing\GraphQL\Security\AuthorizationService;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ServerConfig::class => ServerConfigFactory::class,
                StandardServer::class => StandardServerFactory::class,
                AuthenticationServiceInterface::class => AuthenticationServiceFactory::class,
                AuthorizationServiceInterface::class => AuthorizationService::class,
                HttpCodeDeciderInterface::class => HttpCodeDecider::class,
                Schema::class => SchemaFactory::class,
            ],
            'listeners' => [
                BootGraphQLListener::class,
            ],
            'commands' => [
                GenerateGraphQLSchemaCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for graphql.',
                    'source' => __DIR__ . '/../publish/graphql.php',
                    'destination' => BASE_PATH . '/config/autoload/graphql.php',
                ],
            ]
        ];
    }
}
