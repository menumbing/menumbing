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
namespace Menumbing\Tracer;

use Menumbing\Tracer\Aspect\GuzzleHttpClientHandlerFactoryAspect;
use Menumbing\Tracer\Listener\DisableTraceHyperfGuzzle;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                DisableTraceHyperfGuzzle::class,
            ],
            'aspects' => [
                GuzzleHttpClientHandlerFactoryAspect::class,
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
                    'description' => 'The config for tracer.',
                    'source' => __DIR__ . '/../publish/opentracing.php',
                    'destination' => BASE_PATH . '/config/autoload/opentracing.php',
                ],
            ],
        ];
    }
}
