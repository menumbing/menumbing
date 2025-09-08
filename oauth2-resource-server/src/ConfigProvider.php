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

namespace Menumbing\OAuth2\ResourceServer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'publish'      => [
                [
                    'id'          => 'config',
                    'description' => 'The config for oauth2-resource-server.',
                    'source'      => __DIR__ . '/../publish/oauth2_resource_server.php',
                    'destination' => BASE_PATH . '/config/autoload/oauth2_resource_server.php',
                ],
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
