<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Zipkin\Samplers\BinarySampler;

use function Hyperf\Support\env;

return [
    'default' => env('TRACER_DRIVER', 'zipkin'),
    'enable' => [
        'coroutine' => (bool) env('TRACER_ENABLE_COROUTINE', false),
        'db' => (bool) env('TRACER_ENABLE_DB', false),
        'elasticserach' => (bool) env('TRACER_ENABLE_ELASTICSERACH', false),
        'exception' => (bool) env('TRACER_ENABLE_EXCEPTION', false),
        'grpc' => (bool) env('TRACER_ENABLE_GRPC', false),
        'http_client' => (bool) env('TRACER_ENABLE_HTTP_CLIENT', false),
        'method' => (bool) env('TRACER_ENABLE_METHOD', false),
        'redis' => (bool) env('TRACER_ENABLE_REDIS', false),
        'rpc' => (bool) env('TRACER_ENABLE_RPC', false),
        'request_body' => (bool) env('TRACER_ENABLE_REQUEST_BODY', false),
        'response_body' => (bool) env('TRACER_ENABLE_RESPONSE_BODY', false),
        'event_stream_producer' => (bool) env('TRACER_ENABLE_EVENT_STREAM_PRODUCER', false),
        'event_stream_consumer' => (bool) env('TRACER_ENABLE_EVENT_STREAM_CONSUMER', false),
        'event_stream_payload' => (bool) env('TRACER_ENABLE_EVENT_STREAM_PAYLOAD', false),
        'async_push' => (bool) env('TRACER_ENABLE_ASYNC_PUSH', false),
        'async_consumer' => (bool) env('TRACER_ENABLE_ASYNC_CONSUMER', false),
        'async_payload' => (bool) env('TRACER_ENABLE_ASYNC_PAYLOAD', false),
        'ignore_exceptions' => [],
        'trace_methods' => [],
    ],
    'tracer' => [
        'zipkin' => [
            'driver' => Menumbing\Tracer\Adapter\ZipkinTracerFactory::class,
            'app' => [
                'name' => env('APP_NAME', 'skeleton'),
                // Hyperf will detect the system info automatically as the value if ipv4, ipv6, port is null
                'ipv4' => null,
                'ipv6' => null,
                'port' => null,
            ],
            'reporter' => env('ZIPKIN_REPORTER', 'http'), // kafka, http
            'reporters' => [
                // options for http reporter
                'http' => [
                    'class' => \Zipkin\Reporters\Http::class,
                    'constructor' => [
                        'options' => [
                            'endpoint_url' => env('ZIPKIN_ENDPOINT_URL', 'http://localhost:9411/api/v2/spans'),
                            'timeout' => (int) env('ZIPKIN_TIMEOUT', 1),
                        ],
                    ],
                ],
                // options for kafka reporter
                'kafka' => [
                    'class' => \Hyperf\Tracer\Adapter\Reporter\Kafka::class,
                    'constructor' => [
                        'options' => [
                            'topic' => env('ZIPKIN_KAFKA_TOPIC', 'zipkin'),
                            'bootstrap_servers' => env('ZIPKIN_KAFKA_BOOTSTRAP_SERVERS', '127.0.0.1:9092'),
                            'acks' => (int) env('ZIPKIN_KAFKA_ACKS', -1),
                            'connect_timeout' => (int) env('ZIPKIN_KAFKA_CONNECT_TIMEOUT', 1),
                            'send_timeout' => (int) env('ZIPKIN_KAFKA_SEND_TIMEOUT', 1),
                        ],
                    ],
                ],
                'noop' => [
                    'class' => \Zipkin\Reporters\Noop::class,
                ],
            ],
            'sampler' => BinarySampler::createAsAlwaysSample(),
            'trace_id_128bits' => false,
        ],
        'jaeger' => [
            'driver' => Hyperf\Tracer\Adapter\JaegerTracerFactory::class,
            'name' => env('APP_NAME', 'skeleton'),
            'options' => [
                /*
                 * You can uncomment the sampler lines to use custom strategy.
                 *
                 * For more available configurations,
                 * @see https://github.com/jonahgeorge/jaeger-client-php
                 */
                // 'sampler' => [
                //     'type' => \Jaeger\SAMPLER_TYPE_CONST,
                //     'param' => true,
                // ],,
                'local_agent' => [
                    'reporting_host' => env('JAEGER_REPORTING_HOST', 'localhost'),
                    'reporting_port' => env('JAEGER_REPORTING_PORT', 5775),
                ],
            ],
        ],
    ],
    'tags' => [
        'http_client' => [
            'http.url' => 'http.url',
            'http.method' => 'http.method',
            'http.status_code' => 'http.status_code',
        ],
        'redis' => [
            'arguments' => 'arguments',
            'result' => 'result',
        ],
        'db' => [
            'db.query' => 'db.query',
            'db.statement' => 'db.statement',
            'db.query_time' => 'db.query_time',
        ],
        'exception' => [
            'class' => 'exception.class',
            'code' => 'exception.code',
            'message' => 'exception.message',
            'stack_trace' => 'exception.stack_trace',
        ],
        'request' => [
            'path' => 'request.path',
            'uri' => 'request.uri',
            'method' => 'request.method',
            'header' => 'request.header',
        ],
        'coroutine' => [
            'id' => 'coroutine.id',
        ],
        'response' => [
            'status_code' => 'response.status_code',
        ],
        'rpc' => [
            'path' => 'rpc.path',
            'status' => 'rpc.status',
        ],
    ],
];
