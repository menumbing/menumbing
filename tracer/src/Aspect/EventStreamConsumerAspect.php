<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Context\Context;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Handler\ConsumerEventHandler;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EventStreamConsumerAspect extends AbstractAspect
{
    use SpanStarter;

    public array $classes = [
        ConsumerEventHandler::class.'::handle',
    ];

    public function __construct(private readonly SwitchManager $switchManager)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $this->reset();

        $tracer = TracerContext::getTracer();
        $span = $this->initSpan($tracer, $proceedingJoinPoint->arguments['keys']['message']);

        if ($this->switchManager->isEnable('event_stream_consumer') === false) {
            return $proceedingJoinPoint->process();
        }

        try {
            $result = $proceedingJoinPoint->process();
        } catch (Throwable $e) {
            $span->setTag('error', true);

            if ($this->switchManager->isEnable('exception') && !$this->switchManager->isIgnoreException($e)) {
                $span->log([
                    'message',
                    $e->getMessage(),
                    'code' => $e->getCode(),
                    'stacktrace' => $e->getTraceAsString(),
                ]);
            }

            throw $e;
        } finally {
            $span->finish();
            $tracer->flush();
        }

        return $result;
    }

    protected function initSpan(Tracer $tracer, StreamMessage $message): Span
    {
        if (!empty($carrier = $message->context['trace'] ?? null)) {
            $parentContext = $tracer->extract(TEXT_MAP, $carrier);
            $span = $this->startSpan('event_stream.consume: '.$message->type, [
                'child_of' => $parentContext,
            ]);
        } else {
            $span = $this->startSpan('event_stream.consume: '.$message->type);
        }

        $span->setTag('event_stream.consume.stream', $message->stream);
        $span->setTag('event_stream.consume.event_type', $message->type);

        return $span;
    }

    protected function reset(): void
    {
        Context::set(TracerContext::TRACER, null);
        Context::set(TracerContext::TRACE_ID, null);
        Context::set(TracerContext::ROOT, null);
    }
}
