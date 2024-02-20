<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Middleware;

use Hyperf\Tracer\TracerContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\Tracing\TraceId;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class SentryTraceIdMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($traceId = TracerContext::getTraceId()) {
            $hub = SentrySdk::getCurrentHub();

            $hub->configureScope(function (Scope $scope) use ($traceId) {
                $scope->getPropagationContext()->setTraceId(new TraceId($traceId));
            });
        }

        return $handler->handle($request);
    }
}
