<?php

use GraphQL\Error\DebugFlag;
use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Menumbing\GraphQL;

use function Hyperf\Support\env;

return [
    'debug' => env('GRAPHQL_DEBUG', DebugFlag::RETHROW_UNSAFE_EXCEPTIONS),

    'uri' => '/graphql',

    'cache' => GraphQL\Cache\MemoryCache::class,

    'controllers' => [
        'App\\Controllers',
    ],

    'types' => [
        'App\\',
    ],

    'type_mappers' => [],

    'type_mapper_factories' => [
        GraphQL\Factory\PaginatorTypeMapperFactory::class,
    ],

    'parameter_middlewares' => [
        GraphQL\Parameter\Middleware\ValidateParameterMiddleware::class,
    ],

    'authentication' => [
        'class_name' => Graphql\Security\AuthenticationService::class,
        'guards'     => null,
    ],

    'input_validator' => GraphQL\Validator\InputTypeValidator::class,

    'http_code_decider' => TheCodingMachine\GraphQLite\Http\HttpCodeDecider::class,

    'server' => [
        'enabled' => true,
        'name' => 'graphql',
        'type' => Server::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => 4000,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
            Event::ON_REQUEST => [GraphQL\GraphQLServer::class, 'onRequest'],
        ],
        'options' => [
            // Whether to enable request lifecycle event
            'enable_request_lifecycle' => false,
        ],
    ],

    'print_schema' => [
        'enabled' => true,
        'uri' => '/graphql/schema',
        'middlewares' => [],
    ]
];
