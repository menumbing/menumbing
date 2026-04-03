<?php

declare(strict_types=1);

namespace Menumbing\GracefulProcess\Middleware;

use Menumbing\GracefulProcess\GracefulShutdownCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rejects new HTTP requests during graceful shutdown with 503 Service Unavailable.
 *
 * This middleware checks the shared-memory shutdown flag set by
 * GracefulWorkerStopHandler. When shutdown is requested, new requests
 * receive an immediate 503 response instead of being processed.
 *
 * In-flight requests (already past this middleware) continue normally.
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GracefulShutdownMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (GracefulShutdownCollector::isShutdownRequested()) {
            return new \Hyperf\HttpMessage\Server\Response()
                ->withStatus(503)
                ->withAddedHeader('Retry-After', '5')
                ->withAddedHeader('Connection', 'close');
        }

        return $handler->handle($request);
    }
}
