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
namespace Menumbing\Signature;

use Menumbing\Contract\Signature\SignerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                SignerInterface::class => HashSigner::class,
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
                    'id' => 'migration:client',
                    'description' => 'The client migration.',
                    'source' => __DIR__ . '/../publish/migrations/2024_02_08_175440_create_clients_table.php',
                    'destination' => BASE_PATH . '/migrations/2024_02_08_175440_create_clients_table.php',
                ],
            ]
        ];
    }
}
