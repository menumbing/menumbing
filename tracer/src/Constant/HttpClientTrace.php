<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Constant;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HttpClientTrace
{
    const TRACE_OPERATION = 'trace.operation';
    const TRACE_CAPTURE_ALL = 'trace.capture_all';
    const TRACE_CAPTURE_BODY = 'trace.capture_request';
    const TRACE_CAPTURE_HEADERS = 'trace.capture_header';
    const TRACE_CAPTURE_RESPONSE = 'trace.capture_response';
}
