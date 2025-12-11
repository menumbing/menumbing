<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\AsyncQueue\Exception\JobHandlingException;
use Hyperf\AsyncQueue\Handler\JobHandler;
use Hyperf\AsyncQueue\MessageInterface;
use Hyperf\Context\Context;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Tracer\Trait\AsyncSpanStarter;
use Menumbing\Tracer\Trait\ParentContextExtractor;
use Menumbing\Tracer\Trait\SpanErrorHandler;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AsyncConsumerAspect extends AbstractAspect
{
    use AsyncSpanStarter;
    use ParentContextExtractor;
    use SpanErrorHandler;

    public array $classes = [
        JobHandler::class . '::handle',
    ];

    public function __construct(protected SwitchManager $switchManager)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $this->reset();

        $tracer = TracerContext::getTracer();

        /** @var MessageInterface $message */
        $message = $proceedingJoinPoint->arguments['keys']['message'];
        $parentContext = $this->extractParentContext($tracer, $message->job()->getContext()['trace'] ?? []);;
        $job = $message->job();

        $span = $this->capturePayload(
            $this->initSpan($job, 'consume', $parentContext),
            $job
        );

        if (false === $this->switchManager->isEnable('async_consumer')) {
            return $proceedingJoinPoint->process();
        }

        try {
            $result = $proceedingJoinPoint->process();
        } catch (Throwable $e) {
            if ($e instanceof JobHandlingException) {
                $exception = $e->getPrevious();
            } else {
                $exception = $e;
            }

            $this->spanError($this->switchManager, $span, $exception);

            throw $e;
        } finally {
            $span->finish();
            $tracer->flush();
        }

        return $result;
    }

    protected function reset(): void
    {
        Context::set(TracerContext::TRACER, null);
        Context::set(TracerContext::TRACE_ID, null);
        Context::set(TracerContext::ROOT, null);
    }
}
