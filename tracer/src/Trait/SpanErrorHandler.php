<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Trait;

use Hyperf\Tracer\SwitchManager;
use OpenTracing\Span;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait SpanErrorHandler
{
    protected function spanError(SwitchManager $switchManager, Span $span, Throwable $e): void
    {
        $span->setTag('error', true);

        if ($switchManager->isEnable('exception') && !$switchManager->isIgnoreException($e)) {
            $span->log([
                'type',
                get_class($e),
                'message',
                $e->getMessage(),
                'code',
                $e->getCode(),
                'stacktrace',
                $e->getTraceAsString(),
            ]);
        }
    }
}
