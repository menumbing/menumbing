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
namespace Menumbing\Resource;

use Menumbing\Resource\Contract\RequestIpAddressInterface;
use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Menumbing\Resource\Factory\ResourceStrategyFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                RequestIpAddressInterface::class => RequestIpAddress::class,
                ResourceStrategyInterface::class => ResourceStrategyFactory::class,
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
                    'description' => 'The config for resource.',
                    'source' => __DIR__ . '/../publish/resource.php',
                    'destination' => BASE_PATH . '/config/autoload/resource.php',
                ],
            ]
        ];
    }
}
