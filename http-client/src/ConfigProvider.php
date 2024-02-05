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
namespace Menumbing\HttpClient;

use Menumbing\Contract\HttpClient\HttpClientFactoryInterface;
use Menumbing\HttpClient\Factory\GuzzleHttpClientFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        // Register property injector
        RegisterHttpClientPropertyHandler::register();

        return [
            'dependencies' => [
                HttpClientFactoryInterface::class => GuzzleHttpClientFactory::class,
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
                    'description' => 'The config for http client.',
                    'source' => __DIR__ . '/../publish/http_client.php',
                    'destination' => BASE_PATH . '/config/autoload/http_client.php',
                ],
            ]
        ];
    }
}
