<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\TracerContext;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Handler\ProduceEventHandler;
use OpenTracing\SpanContext;

use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EventStreamPublisherAspect extends AbstractAspect
{
    use SpanStarter;

    public array $classes = [
        ProduceEventHandler::class . '::produce',
    ];

    public function __construct(private readonly ConfigInterface $config)
    {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        if ($this->config->get('opentracing.enable.event_stream_producer', false) === false) {
            return $proceedingJoinPoint->process();
        }

        /** @var StreamMessage $message */
        $message = $proceedingJoinPoint->arguments['keys']['message'];

        $span = $this->startSpan('event_stream.produce: ' . $message->type);
        $span->setTag('event_stream.produce.stream', $message->stream);
        $span->setTag('event_stream.produce.event_type', $message->type);

        $proceedingJoinPoint->arguments['keys']['message'] = $this->injectContext($message, $span->getContext());

        try {
            $result = $proceedingJoinPoint->process();
        } catch (\Throwable $e) {
            $span->setTag('error', true);

            if ($this->switchManager->isEnable('exception') && ! $this->switchManager->isIgnoreException($e)) {
                $span->log(['message', $e->getMessage(), 'code' => $e->getCode(), 'stacktrace' => $e->getTraceAsString()]);
            }

            throw $e;
        } finally {
            $span->finish();
        }

        return $result;
    }

    protected function injectContext(StreamMessage $message, SpanContext $spanContext): StreamMessage
    {
        $carrier = [];
        TracerContext::getTracer()->inject($spanContext, TEXT_MAP, $carrier);

        return $message->withContext([
            'trace' => $carrier,
        ]);
    }
}
