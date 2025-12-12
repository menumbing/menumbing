<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Redis\Redis;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Tracer\Trait\SpanErrorHandler;
use Throwable;

use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class RedisAspect extends AbstractAspect
{
    use SpanStarter;
    use SpanErrorHandler;

    public ?int $priority = 99;

    public array $classes = [
        Redis::class . '::__call',
    ];

    public function __construct(private readonly SwitchManager $switchManager, private readonly SpanTagManager $spanTagManager)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if ($this->switchManager->isEnable('redis_call') === false) {
            return $proceedingJoinPoint->process();
        }

        $arguments = $proceedingJoinPoint->arguments['keys'];
        $span = $this->startSpan('redis.' . $arguments['name'], kind: SPAN_KIND_RPC_CLIENT);
        TracerContext::setRoot($span);

        $span->setTag($this->spanTagManager->get('redis', 'arguments'), json_encode($arguments['arguments']));

        try {
            $result = $proceedingJoinPoint->process();
            $span->setTag($this->spanTagManager->get('redis', 'result'), json_encode($result));
        } catch (Throwable $e) {
            $this->spanError($this->switchManager, $span, $e);

            throw $e;
        } finally {
            $span->finish();
        }

        return $result;
    }
}
