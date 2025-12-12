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

use Menumbing\Tracer\Aspect\AsyncConsumerAspect;
use Menumbing\Tracer\Aspect\AsyncPushAspect;
use Menumbing\Tracer\Aspect\EventStreamConsumerAspect;
use Menumbing\Tracer\Aspect\EventStreamPublisherAspect;
use Menumbing\Tracer\Aspect\GuzzleHttpClientHandlerFactoryAspect;
use Menumbing\Tracer\Listener\DbQueryExecutedListener;
use Menumbing\Tracer\Listener\DisableDefaultTraceHyperf;
use Menumbing\Tracer\Listener\RedisCommandExecutedListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                DisableDefaultTraceHyperf::class,
                DbQueryExecutedListener::class,
                RedisCommandExecutedListener::class,
            ],
            'aspects' => [
                GuzzleHttpClientHandlerFactoryAspect::class,
                EventStreamPublisherAspect::class,
                EventStreamConsumerAspect::class,
                AsyncPushAspect::class,
                AsyncConsumerAspect::class,
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
