<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use OpenTracing\Span;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function Hyperf\Coroutine\defer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class TraceMiddleware implements MiddlewareInterface
{
    use SpanStarter;

    public function __construct(
        protected readonly SwitchManager $switchManager,
        protected readonly SpanTagManager $spanTagManager,
        protected readonly ExceptionHandlerDispatcher $exceptionHandler,
        protected readonly ConfigInterface $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tracer = TracerContext::getTracer();
        $span = $this->buildRequestSpan($request);

        defer(function () use ($tracer) {
            try {
                $tracer->flush();
            } catch (\Throwable) {
            }
        });
        try {
            $response = $handler->handle($request);

            $this->buildResponseSpan($span, $response);

            if ($traceId = TracerContext::getTraceId()) {
                $response = $response->withHeader('Trace-Id', $traceId);
            }
            $span->setTag($this->spanTagManager->get('response', 'status_code'), (string) $response->getStatusCode());
        } catch (Throwable $exception) {
            $this->appendExceptionToSpan($span, $exception);

            if ($exception instanceof HttpException) {
                $span->setTag($this->spanTagManager->get('response', 'status_code'), (string) $exception->getStatusCode());
            }

            /** @var Dispatched $dispatched */
            $dispatched = $request->getAttribute(Dispatched::class);
            $response = $this->exceptionHandler->dispatch(
                $exception, $this->config->get('exceptions.handler.' . $dispatched->serverName)
            );

            $this->buildResponseSpan($span, $response);

            throw $exception;
        } finally {
            $span->finish();
        }

        return $response;
    }

    protected function appendExceptionToSpan(Span $span, Throwable $exception): void
    {
        $span->setTag('error', true);

        if ($this->switchManager->isEnable('exception') && ! $this->switchManager->isIgnoreException($exception)) {
            $span->setTag($this->spanTagManager->get('exception', 'class'), get_class($exception));
            $span->setTag($this->spanTagManager->get('exception', 'code'), (string) $exception->getCode());
            $span->setTag($this->spanTagManager->get('exception', 'message'), $exception->getMessage());
            $span->setTag($this->spanTagManager->get('exception', 'stack_trace'), (string) $exception);
        }
    }

    protected function buildRequestSpan(ServerRequestInterface $request): Span
    {
        $uri = $request->getUri();
        $span = $this->startSpan(sprintf('request: %s %s', $request->getMethod(), $uri->getPath()));
        $span->setTag($this->spanTagManager->get('coroutine', 'id'), (string) Coroutine::id());
        $span->setTag($this->spanTagManager->get('request', 'path'), $uri->getPath());
        $span->setTag($this->spanTagManager->get('request', 'method'), $request->getMethod());
        $span->setTag($this->spanTagManager->get('request', 'uri'), (string) $uri);

        foreach ($request->getHeaders() as $key => $value) {
            $span->setTag($this->spanTagManager->get('request', 'header') . '.' . $key, implode(', ', $value));
        }

        if ($this->switchManager->isEnable('request_body')) {
            $span->setTag('request.body', (string) $request->getBody());
        }

        return $span;
    }

    protected function buildResponseSpan(Span $span, ResponseInterface $response): Span
    {
        if ($this->switchManager->isEnable('response_body')) {
            $span->setTag('response.body', (string) $response->getBody());
        }

        return $span;
    }
}
