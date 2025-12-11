<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Trait;

use Hyperf\AsyncQueue\AnnotationJob;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Stringable\Str;
use Hyperf\Tracer\SpanStarter;
use OpenTracing\Span;
use OpenTracing\SpanContext;

use function Hyperf\Support\class_basename;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait AsyncSpanStarter
{
    use SpanStarter;

    protected function initSpan(JobInterface $job, string $spanType, string $spanKind, SpanContext $parentContext = null): Span
    {
        $jobInfo = $this->parseJobInfo($job);
        $spanName = sprintf('async.%s: %s', $spanType, $jobInfo['name']);

        if ($parentContext) {
            $span = $this->startSpan($spanName, ['child_of' => $parentContext], $spanKind);
        } else {
            $span = $this->startSpan($spanName, kind: $spanKind);
        }

        $span->setTag('async.job', $jobInfo['job']);
        $span->setTag('async.max_attempts', $jobInfo['max_attempts']);

        return $span;
    }

    protected function capturePayload(Span $span, JobInterface $job): Span
    {
        if (false === $this->switchManager->isEnable('async_payload')) {
            return $span;
        }

        $span->setTag('async.payload', serialize($job));

        return $span;
    }

    protected function parseJobInfo(JobInterface $job): array
    {
        if ($job instanceof AnnotationJob) {
            $name = class_basename($job->class) . '::' . $job->method;
            $jobHandler = $job->class . '::' . $job->method;
        } else {
            $name = class_basename($job);
            $jobHandler = get_class($job);
        }

        return [
            'name' => Str::kebab($name),
            'job' => $jobHandler,
            'max_attempts' => $job->getMaxAttempts(),
        ];
    }
}
