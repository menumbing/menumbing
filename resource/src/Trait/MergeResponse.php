<?php

declare(strict_types=1);

namespace Menumbing\Resource\Trait;

use Hyperf\Tracer\TracerContext;
use Psr\Http\Message\ResponseInterface;

use function Hyperf\Config\config;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait MergeResponse
{
    protected function mergeAll(ResponseInterface $source, ResponseInterface $destination): ResponseInterface
    {
        return $this->addTraceId(
            $this->addServerName(
                $this->merge($source, $destination)
            )
        );
    }

    protected function merge(ResponseInterface $source, ResponseInterface $destination): ResponseInterface
    {
        foreach ($source->getHeaders() as $header => $values) {
            if (!$destination->hasHeader($header)) {
                $destination = $destination->withHeader($header, $values);
            }
        }

        return $destination;
    }

    protected function addTraceId(ResponseInterface $response): ResponseInterface
    {
        if (!class_exists('\Hyperf\Tracer\TracerContext')) {
            return $response;
        }

        if (!$response->hasHeader('Trace-Id') && ($traceId = TracerContext::getTraceId())) {
            $response = $response->withHeader('Trace-Id', $traceId);
        }

        return $response;
    }

    protected function addServerName(ResponseInterface $response): ResponseInterface
    {
        if ($response->hasHeader('Server')) {
            return $response;
        }

        return $response->withHeader('Server', config('resource.server_name', 'Hyperf'));
    }
}
