<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use Menumbing\Tracer\Trait\SpanErrorHandler;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class MethodAspect extends AbstractAspect
{
    use SpanStarter;
    use SpanErrorHandler;

    public function __construct(protected SwitchManager $switchManager)
    {
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
            $this->spanError($this->switchManager, $span, $e);

            throw $e;
        } finally {
            $span->finish();

            TracerContext::setRoot($rootSpan);
        }

        return $result;
    }
}
