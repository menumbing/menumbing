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

                $response->then(function (ResponseInterface $response) use ($span, $options) {
                    $this->appendResponseSpanTags($span, $response, $options);

                    return $response;
                });

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
        $span->setTag('http.response.status_code', $response->getStatusCode());

        if ($response->getStatusCode() >= 400) {
            $span->setTag('error', true);
            $span->setTag('http.response.body', (string) $response->getBody());
        } else if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_RESPONSE)) {
            $span->setTag('http.response.body', (string) $response->getBody());
        }

        $span->finish();
    }

    protected function appendRequestSpanTags(Span $span, RequestInterface $request, array $options): void
    {
        $span->setTag('http.request.url', (string) $request->getUri());
        $span->setTag('http.request.path', $request->getUri()->getPath());
        $span->setTag('http.request.method', $request->getMethod());

        if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_HEADERS)) {
            foreach ($request->getHeaders() as $header => $values) {
                $span->setTag('http.request.header.' . $header, implode(', ', $values));
            }
        }

        if ($this->isCapture($options, HttpClientTrace::TRACE_CAPTURE_BODY)) {
            if (!empty($content = (string) $request->getBody())) {
                $span->setTag('http.request.body', $content);
            }
        }
    }

    protected function getSpanName(RequestInterface $request, array $options): string
    {
        if (null !== $operation = $this->getTraceOperation($options)) {
            return "HTTP Request {$operation}";
        }

        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        return "HTTP Request [{$method}] {$uri}";
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
