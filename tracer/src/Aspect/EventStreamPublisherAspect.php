<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Handler\ProduceEventHandler;
use Menumbing\Serializer\Factory\SerializerFactory;
use OpenTracing\Span;
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

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly SwitchManager $switchManager,
        private readonly SerializerFactory $serializerFactory,
    ) {
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        if (false === $this->switchManager->isEnable('event_stream_producer')) {
            return $proceedingJoinPoint->process();
        }

        /** @var StreamMessage $message */
        $message = $proceedingJoinPoint->arguments['keys']['message'];

        $span = $this->capturePayload(
            $this->initSpan($message),
            $message
        );

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

    protected function initSpan(StreamMessage $message): Span
    {
        $span = $this->startSpan('event_stream.produce: ' . $message->type);
        $span->setTag('event_stream.produce.stream', $message->stream);
        $span->setTag('event_stream.produce.event_type', $message->type);

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

    protected function injectContext(StreamMessage $message, SpanContext $spanContext): StreamMessage
    {
        $carrier = [];
        TracerContext::getTracer()->inject($spanContext, TEXT_MAP, $carrier);

        return $message->withContext([
            'trace' => $carrier,
        ]);
    }
}
