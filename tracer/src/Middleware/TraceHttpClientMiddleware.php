<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Hyperf\Guzzle\MiddlewareInterface;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\TracerContext;
use Menumbing\Tracer\Constant\HttpClientTrace;
use OpenTracing\Span;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class TraceHttpClientMiddleware implements MiddlewareInterface
{
    use SpanStarter;

    public function getMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                $span = $this->startSpan($this->getSpanName($request, $options));

                $this->appendRequestSpanTags($span, $request, $options);

                /** @var PromiseInterface $response */
                $response = $handler($this->injectTracer($span, $request), $options);

                $response->then(
                    function (ResponseInterface $response) use ($span, $options) {
                        $this->appendResponseSpanTags($span, $response, $options);

                        return $response;
                    },
                    function (Throwable $exception) use ($span) {
                        $span->setTag('error', true);

                        $span->setTag('http.response.body', $exception->getMessage());
                    }
                );

                return $response;
            };
        };
    }

    protected function injectTracer(Span $span, RequestInterface $request): RequestInterface
    {
        $appendHeaders = [];

        TracerContext::getTracer()->inject(
            $span->getContext(),
            TEXT_MAP,
            $appendHeaders
        );

        foreach ($appendHeaders as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request;
    }

    protected function appendResponseSpanTags(Span $span, ResponseInterface $response, array $options): void
    {
        $span->setTag('http_client.response.status_code', $response->getStatusCode());

        if ($response->getStatusCode() >= 400) {
            $span->setTag('error', true);
            $span->setTag('http_client.response.body', (string) $response->getBody());
        } else if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_RESPONSE)) {
            $span->setTag('http_client.response.body', (string) $response->getBody());
        }

        $span->finish();
    }

    protected function appendRequestSpanTags(Span $span, RequestInterface $request, array $options): void
    {
        $span->setTag('http_client.request.url', (string) $request->getUri());
        $span->setTag('http_client.request.path', $request->getUri()->getPath());
        $span->setTag('http_client.request.method', $request->getMethod());

        if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_HEADERS)) {
            foreach ($request->getHeaders() as $header => $values) {
                $span->setTag('http_client.request.header.' . $header, implode(', ', $values));
            }
        }

        if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_BODY)) {
            if (!empty($content = (string) $request->getBody())) {
                $span->setTag('http_client.request.body', $content);
            }
        }

        $tags = $options[HttpClientTrace::TRACE_TAGS] ?? [];

        foreach ($tags as $key => $value) {
            $span->setTag($key, $value);
        }
    }

    protected function getSpanName(RequestInterface $request, array $options): string
    {
        if (null !== $operation = $this->getTraceOperation($options)) {
            return "http_client.request: {$operation}";
        }

        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        return "http_client.request: {$method} {$uri}";
    }

    protected function getTraceOperation(array $options): ?string
    {
        return $options[HttpClientTrace::TRACE_OPERATION] ?? null;
    }

    protected function isCapture(array $options, string $key): bool
    {
        return true === ($options[$key] ?? false) ||
            true === ($options[HttpClientTrace::TRACE_CAPTURE_ALL] ?? false);
    }
}
