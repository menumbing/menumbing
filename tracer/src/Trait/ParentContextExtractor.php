<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Trait;

use OpenTracing\SpanContext;
use OpenTracing\Tracer;

use const OpenTracing\Formats\TEXT_MAP;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait ParentContextExtractor
{
    protected function extractParentContext(Tracer $tracer, array $carrier): ?SpanContext
    {
        if (!empty($carrier)) {
            return $tracer->extract(TEXT_MAP, $carrier);
        }

        return null;
    }
}
