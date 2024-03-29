<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Exception\Handler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Tracer\TracerContext;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class RespondTraceIdExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponsePlusInterface $response): ResponsePlusInterface
    {
        if (!$response->hasHeader('Trace-Id') && ($traceId = TracerContext::getTraceId())) {
            $response = $response->withHeader('Trace-Id', $traceId);
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
