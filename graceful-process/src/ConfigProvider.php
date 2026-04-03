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

namespace Menumbing\GracefulProcess;

use Menumbing\GracefulProcess\Listener\GracefulShutdownListener;
use Menumbing\GracefulProcess\Listener\ShutdownWatcherListener;
use Menumbing\GracefulProcess\Middleware\GracefulShutdownMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'middlewares' => [
                'http' => [
                    GracefulShutdownMiddleware::class,
                ],
            ],
            'listeners' => [
                GracefulShutdownListener::class,
                ShutdownWatcherListener::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for graceful-process.',
                    'source' => __DIR__ . '/../publish/graceful_process.php',
                    'destination' => BASE_PATH . '/config/autoload/graceful_process.php',
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
