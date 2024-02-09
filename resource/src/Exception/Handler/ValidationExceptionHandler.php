<?php

declare(strict_types=1);

namespace Menumbing\Resource\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Validation\ValidationException;
use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Menumbing\Resource\Resource\ValidationErrorResource;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ValidationExceptionHandler extends ExceptionHandler
{
    #[Inject]
    protected ResourceStrategyInterface $resource;

    /**
     * @param  ValidationException  $throwable
     * @param  ResponsePlusInterface  $response
     *
     * @return mixed|void
     */
    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        if ($this->resource->supports($throwable)) {
            $this->stopPropagation();

            return $this->resource->render(new ValidationErrorResource($throwable));
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
