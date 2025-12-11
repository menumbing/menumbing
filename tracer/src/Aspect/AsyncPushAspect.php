<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\AsyncQueue\Driver\DriverManager;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Tracer\Trait\AsyncSpanStarter;
use Menumbing\Tracer\Trait\SpanErrorHandler;
use OpenTracing\SpanContext;

use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AsyncPushAspect extends AbstractAspect
{
    use AsyncSpanStarter;
    use SpanErrorHandler;

    public array $classes = [
        DriverManager::class . '::push',
    ];

    public function __construct(private readonly SwitchManager $switchManager)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if (false === $this->switchManager->isEnable('async_push')) {
            return $proceedingJoinPoint->process();
        }

        /** @var JobInterface $job */
        $job = $proceedingJoinPoint->arguments['keys']['job'];

        $span = $this->capturePayload($this->initSpan($job, 'push'), $job);

        $proceedingJoinPoint->arguments['keys']['job'] = $this->injectContext($job, $span->getContext());

        try {
            $result = $proceedingJoinPoint->process();
        } catch (\Throwable $e) {
            $this->spanError($this->switchManager, $span, $e);

            throw $e;
        } finally {
            $span->finish();
        }

        return $result;
    }

    protected function injectContext(JobInterface $job, SpanContext $spanContext): JobInterface
    {
        $carrier = [];
        TracerContext::getTracer()->inject($spanContext, TEXT_MAP, $carrier);

        return $job->withContext(['trace' => $carrier]);
    }
}
