<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use Hyperf\Contract\ConfigInterface;
use HyperfExtension\Auth\Contracts\AuthManagerInterface;
use Menumbing\GraphQL\Security\AuthenticationService;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AuthenticationServiceFactory
{
    public function __invoke(ContainerInterface $container): AuthenticationServiceInterface
    {
        $config = $container->get(ConfigInterface::class);
        $class = $config->get('graphql.authentication.class_name', AuthenticationService::class);
        $guards = (array) $config->get('graphql.authentication.guards', [$config->get('auth.default.guard')]);

        return new $class($container->get(AuthManagerInterface::class), $guards);
    }
}
