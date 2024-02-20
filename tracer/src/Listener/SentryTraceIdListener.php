<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\HttpServer\Event\RequestReceived;
use Hyperf\Tracer\TracerContext;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\Tracing\TraceId;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class SentryTraceIdListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            RequestReceived::class,
        ];
    }

    public function process(object $event): void
    {
        if ($traceId = TracerContext::getTraceId()) {
            $hub = SentrySdk::getCurrentHub();

            $hub->configureScope(function (Scope $scope) use ($traceId) {
                $scope->getPropagationContext()->setTraceId(new TraceId($traceId));
            });
        }
    }
}
