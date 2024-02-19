<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MethodAspect extends AbstractAspect
{
    use SpanStarter;

    public function __construct(protected SwitchManager $switchManager, ConfigInterface $config)
    {
        $this->classes = $config->get('opentracing.enable.trace_methods', []);
    }

    /**
     * @return mixed return the value from process method of ProceedingJoinPoint, or the value that you handled
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        if ($this->switchManager->isEnable('method') === false) {
            return $proceedingJoinPoint->process();
        }

        $rootSpan = TracerContext::getRoot();
        $key = $proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName;
        $span = $this->startSpan($key);

        TracerContext::setRoot($span);

        try {
            $result = $proceedingJoinPoint->process();
        } catch (Throwable $e) {
            if ($this->switchManager->isEnable('exception') && ! $this->switchManager->isIgnoreException($e)) {
                $span->setTag('error', true);
                $span->log(['message', $e->getMessage(), 'code' => $e->getCode(), 'stacktrace' => $e->getTraceAsString()]);
            }
            throw $e;
        } finally {
            $span->finish();

            TracerContext::setRoot($rootSpan);
        }

        return $result;
    }
}
