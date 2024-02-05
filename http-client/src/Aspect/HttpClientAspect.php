<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Menumbing\HttpClient\Annotation\HttpClient;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Aspect]
final class HttpClientAspect extends AbstractAspect
{
    public array $annotations = [
        HttpClient::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return $proceedingJoinPoint->process();
    }
}
