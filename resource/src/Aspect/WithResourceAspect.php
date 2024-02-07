<?php

declare(strict_types=1);

namespace Menumbing\Resource\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Resource\Json\JsonResource;
use Menumbing\Resource\Annotation\WithResource;
use Menumbing\Resource\Contract\ResourceStrategyInterface;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Aspect]
class WithResourceAspect extends AbstractAspect
{
    public array $annotations = [
        WithResource::class,
    ];

    #[Inject]
    protected ResourceStrategyInterface $resource;

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $annotation = $proceedingJoinPoint->getAnnotationMetadata();

        /** @var WithResource $resourceAnnotation */
        $resourceAnnotation = $annotation->class[WithResource::class] ?? $annotation->method[WithResource::class];

        $result = $proceedingJoinPoint->process();

        if ($this->resource->supports($result)) {
            if (isset($resourceAnnotation->resource)) {
                $result = make($resourceAnnotation->resource, [$result]);
            }

            $resource = $this->resource->render($result);

            if ($resource instanceof JsonResource) {
                return $resource->toResponse()->withStatus($resourceAnnotation->statusCode);
            }

            return $resource;
        }

        return $result;
    }
}
