<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use GuzzleHttp\HandlerStack;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SwitchManager;
use Menumbing\HttpClient\Factory\GuzzleHttpClientHandlerFactory;
use Menumbing\Tracer\Middleware\TraceHttpClientMiddleware;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GuzzleHttpClientHandlerFactoryAspect extends AbstractAspect
{
    public array $classes = [
        GuzzleHttpClientHandlerFactory::class . '::create',
    ];

    public function __construct(private readonly SwitchManager $switchManager)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $result = $proceedingJoinPoint->process();

        if ($result instanceof HandlerStack && $this->switchManager->isEnable('http_client')) {
            $result->push(make(TraceHttpClientMiddleware::class)->getMiddleware());
        }

        return $result;
    }
}
