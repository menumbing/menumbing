<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Adapter;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Tracer\Adapter\Reporter\ReporterFactory;
use Hyperf\Tracer\Contract\NamedFactoryInterface;
use OpenTracing\Tracer as TracerContract;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;
use ZipkinOpenTracing\Tracer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ZipkinTracerFactory implements NamedFactoryInterface
{
    private string $prefix = 'opentracing.tracer.';

    private string $name = '';

    public function __construct(private ConfigInterface $config, private ReporterFactory $reportFactory)
    {
    }

    public function make(string $name): TracerContract
    {
        $this->name = $name;
        [$app, $sampler, $reporterOption, $traceId128bits] = $this->parseConfig();
        $endpoint = Endpoint::create($app['name'], $app['ipv4'], $app['ipv6'], $app['port']);
        $reporter = $this->reportFactory->make($reporterOption);
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingTraceId128bits($traceId128bits)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        return new Tracer($tracing);
    }

    private function parseConfig(): array
    {
        // @TODO Detect the ipv4, ipv6, port from server object or system info automatically.
        $reporter = (string) $this->getConfig('reporter', 'http');
        return [
            $this->getConfig('app', [
                'name' => 'skeleton',
                'ipv4' => '127.0.0.1',
                'ipv6' => null,
                'port' => 9501,
            ]),
            $this->getConfig('sampler', BinarySampler::createAsAlwaysSample()),
            $this->getConfig('reporters.' . $reporter, [
                'class' => \Zipkin\Reporters\Http::class,
                'constructor' => [
                    'options' => $this->getConfig('options', []),
                ],
            ]),
            $this->getConfig('trace_id_128bits', false),
        ];
    }

    private function getConfig(string $key, $default)
    {
        return $this->config->get($this->getPrefix() . $key, $default);
    }

    private function getPrefix(): string
    {
        return rtrim($this->prefix . $this->name, '.') . '.';
    }
}
