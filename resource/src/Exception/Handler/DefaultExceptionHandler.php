<?php

declare(strict_types=1);

namespace Menumbing\Resource\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DefaultExceptionHandler extends ExceptionHandler
{
    #[Inject]
    protected ResourceStrategyInterface $resource;

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->stopPropagation();

        if ($this->resource->supports($throwable)) {
            return $this->resource->render($throwable);
        }

        return $response
            ->setStatus($this->getStatusCode($throwable))
            ->setBody(new SwooleStream($throwable->getMessage()));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        if ($throwable instanceof HttpException) {
            return $throwable->getStatusCode();
        }

        if ($throwable->getCode()
            && is_int($throwable->getCode())
            && $throwable->getCode() >= 400
            && $throwable->getCode() < 500) {
            return $throwable->getCode();
        }

        return 500;
    }
}
