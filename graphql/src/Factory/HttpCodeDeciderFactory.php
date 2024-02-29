<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Factory;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HttpCodeDeciderFactory
{
    public function __invoke(ContainerInterface $container): HttpCodeDeciderInterface
    {
        $config = $container->get(ConfigInterface::class);
        $deciderClass = $config->get('graphql.http_code_decider', HttpCodeDecider::class);

        return new $deciderClass();
    }
}
