<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Listener;

use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpServer\Event\RequestHandled;
use Hyperf\HttpServer\Event\RequestTerminated;
use Hyperf\Tracer\Listener\RequestTraceListener as BaseRequestTraceListener;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use OpenTracing\Span;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class RequestTraceListener extends BaseRequestTraceListener
{
    public function __construct(protected SwitchManager $switchManager, protected SpanTagManager $spanTagManager)
    {
    }

    #[Override]
    protected function handleRequestTerminated(RequestTerminated $event): void
    {
        $response = $event->response;

        if (! $response) {
            return;
        }

        $tracer = TracerContext::getTracer();
        $span = TracerContext::getRoot();
        $span->setTag($this->spanTagManager->get('response', 'status_code'), (string) $response->getStatusCode());

        if ($event->exception) {
            $span->setTag('error', true);

            if ($this->switchManager->isEnable('exception') && ! $this->switchManager->isIgnoreException($event->exception)) {
                $this->appendExceptionToSpan($span, $exception = $event->exception);

                if ($exception instanceof HttpException) {
                    $span->setTag($this->spanTagManager->get('response', 'status_code'), $exception->getStatusCode());
                }
            }
        }

        $span->finish();
        $tracer->flush();
    }

    #[Override]
    protected function handleRequestHandled(RequestHandled $event): void
    {
        parent::handleRequestHandled($event);

        $span = TracerContext::getRoot();

        if ($this->switchManager->isEnable('response_body')) {
            $span->setTag('response.body', (string) $event->response->getBody());
        }
    }

    #[Override]
    protected function buildSpan(ServerRequestInterface $request): Span
    {
        $span = parent::buildSpan($request);

        if ($this->switchManager->isEnable('request_body')) {
            $span->setTag('request.body', (string) $request->getBody());
        }

        return $span;
    }
}
