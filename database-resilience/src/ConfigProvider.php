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
namespace Menumbing\Database\Resilience;

use Hyperf\Database\Connectors\ConnectionFactory as HyperfConnectionFactory;
use Hyperf\Di\Definition\PriorityDefinition;
use Menumbing\Database\Resilience\Connectors\ConnectionFactory;

class ConfigProvider
{
    /**
     * hyperf/db-connection binds ConnectionFactory::class to itself in its own ConfigProvider.
     *
     * Hyperf\Config\ProviderConfig::merge() rebuilds the dependencies map in load order and lets
     * a plain string overwrite whatever came before it, so a normal binding here would win or lose
     * depending on the order composer reports the packages in. A PriorityDefinition is the one
     * form that merge() never overwrites with a plain string, which makes this binding win
     * regardless of load order. Hyperf\Di\Definition\DefinitionSource::normalizeDefinition()
     * unwraps it back into the class name.
     */
    public const CONNECTION_FACTORY_PRIORITY = 100;

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HyperfConnectionFactory::class => new PriorityDefinition(
                    ConnectionFactory::class,
                    self::CONNECTION_FACTORY_PRIORITY
                ),
            ],
        ];
    }
}
