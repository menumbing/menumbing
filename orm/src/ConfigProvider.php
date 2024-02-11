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
namespace Menumbing\Orm;

use Hyperf\Database\Commands\Migrations\InstallCommand;
use Hyperf\Database\Commands\Migrations\MigrateCommand;
use Hyperf\Database\Commands\Seeders\SeedCommand;
use Menumbing\Orm\Contract\PersistentInterface;
use Menumbing\Orm\Contract\QueryBuilderFactoryInterface;
use Menumbing\Orm\Contract\RepositoryFactoryInterface;
use Menumbing\Orm\Factory\PersistentFactory;
use Menumbing\Orm\Factory\QueryBuilderFactory;
use Menumbing\Orm\Factory\RepositoryFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                QueryBuilderFactoryInterface::class => QueryBuilderFactory::class,
                RepositoryFactoryInterface::class => RepositoryFactory::class,
                PersistentInterface::class => PersistentFactory::class,
            ],
            'commands' => [
                MigrateCommand::class,
                InstallCommand::class,
                SeedCommand::class,
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
                    'description' => 'The config for orm.',
                    'source' => __DIR__ . '/../publish/orm.php',
                    'destination' => BASE_PATH . '/config/autoload/orm.php',
                ],
            ]
        ];
    }
}
