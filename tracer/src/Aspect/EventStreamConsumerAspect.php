<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Handler\ConsumerEventHandler;
use Menumbing\Serializer\Factory\SerializerFactory;
use Menumbing\Tracer\Trait\SpanErrorHandler;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;
use const OpenTracing\Tags\SPAN_KIND_MESSAGE_BUS_CONSUMER;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class EventStreamConsumerAspect extends AbstractAspect
{
    use SpanStarter;
    use SpanErrorHandler;

    public array $classes = [
        ConsumerEventHandler::class.'::handle',
    ];

    public function __construct(
        private readonly SwitchManager $switchManager,
        private readonly ConfigInterface $config,
        private readonly SerializerFactory $serializerFactory,
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $this->reset();

        $tracer = TracerContext::getTracer();
        $message = $proceedingJoinPoint->arguments['keys']['message'];
        $span = $this->capturePayload(
            $this->initSpan($tracer, $message),
            $message
        );

        if (false === $this->switchManager->isEnable('event_stream_consumer')) {
            return $proceedingJoinPoint->process();
        }

        try {
            $result = $proceedingJoinPoint->process();
        } catch (Throwable $e) {
            $this->spanError($this->switchManager, $span, $e);

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
            $span = $this->startSpan(
                'event_stream.consume: '.$message->type,
                ['child_of' => $parentContext],
                SPAN_KIND_MESSAGE_BUS_CONSUMER
            );
        } else {
            $span = $this->startSpan('event_stream.consume: '.$message->type, kind: SPAN_KIND_MESSAGE_BUS_CONSUMER);
        }

        $span->setTag('event_stream.consume.stream', $message->stream);
        $span->setTag('event_stream.consume.event_type', $message->type);

        return $span;
    }

    protected function capturePayload(Span $span, StreamMessage $message): Span
    {
        if (false === $this->switchManager->isEnable('event_stream_payload')) {
            return $span;
        }

        $span->setTag('event_stream.payload', $this->serialize($message->data));

        return $span;
    }

    protected function serialize(mixed $data): string
    {
        $serializer = $this->serializerFactory->get($this->config->get('event_stream.serialization.serializer', 'default'));

        return $serializer->serialize($data, $this->config->get('event_stream.serialization.format', 'json'));
    }

    protected function reset(): void
    {
        Context::set(TracerContext::TRACER, null);
        Context::set(TracerContext::TRACE_ID, null);
        Context::set(TracerContext::ROOT, null);
    }
}
