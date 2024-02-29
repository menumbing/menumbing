<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use GraphQL\Type\Schema;
use Hyperf\Contract\ConfigInterface;
use Menumbing\GraphQL\Cache\MemoryCache;
use Menumbing\GraphQL\Parameter\Middleware\ValidateParameterMiddleware;
use Menumbing\GraphQL\Validator\InputTypeValidator;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\SchemaFactory as GraphQLiteSchemaFactory;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class SchemaFactory
{
    public function __invoke(ContainerInterface $container): Schema
    {
        $config = $container->get(ConfigInterface::class);
        $schemaFactory = new GraphQLiteSchemaFactory(
            $container->get($config->get('graphql.cache', MemoryCache::class)),
            $container
        );

        $schemaFactory->setAuthenticationService($container->get(AuthenticationServiceInterface::class));
        $schemaFactory->setAuthorizationService($container->get(AuthorizationServiceInterface::class));
        $schemaFactory->setInputTypeValidator($container->get($config->get('graphql.input_validator', InputTypeValidator::class)));

        foreach ($this->getTypeMappers($config) as $typeMapper) {
            $schemaFactory->addTypeMapper($container->get($typeMapper));
        }

        foreach ($this->getTypeMapperFactories($config) as $typeMapperFactory) {
            $schemaFactory->addTypeMapperFactory($container->get($typeMapperFactory));
        }

        foreach ($this->getParameterMiddlewares($config) as $parameterMiddleware) {
            $schemaFactory->addParameterMiddleware($container->get($parameterMiddleware));
        }

        foreach ($this->getControllerNamespaces($config) as $namespace) {
            $schemaFactory->addControllerNamespace($namespace);
        }

        foreach ($this->getTypeNamespaces($config) as $namespace) {
            $schemaFactory->addTypeNamespace($namespace);
        }

        if (in_array($config->get('app_env'), ['prod', 'production'])) {
            $schemaFactory->prodMode();
        }

        return $schemaFactory->createSchema();
    }

    protected function getControllerNamespaces(ConfigInterface $config): array
    {
        return (array) $config->get('graphql.controllers', ['App\\Controllers']);
    }

    protected function getTypeNamespaces(ConfigInterface $config): array
    {
        return (array) $config->get('graphql.types', ['App\\']);
    }

    protected function getTypeMappers(ConfigInterface $config): array
    {
        return (array) $config->get('graphql.type_mappers', []);
    }

    protected function getTypeMapperFactories(ConfigInterface $config): array
    {
        return (array) $config->get('graphql.type_mapper_factories', [
            PaginatorTypeMapperFactory::class,
        ]);
    }

    protected function getParameterMiddlewares(ConfigInterface $config): array
    {
        return (array) $config->get('graphql.parameter_middlewares', [
            ValidateParameterMiddleware::class,
        ]);
    }
}
