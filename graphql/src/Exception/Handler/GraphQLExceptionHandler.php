<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Exception\Handler;

use GraphQL\Error\FormattedError;
use Hyperf\Codec\Json;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class GraphQLExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger, protected FormatterInterface $formatter)
    {
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        $this->logger->debug($this->formatter->format($throwable));

        $this->stopPropagation();

        return $response
            ->setStatus($this->getStatusCode($throwable))
            ->setHeader('Content-Type', 'application/json')
            ->setBody(new SwooleStream($this->parseBody($throwable)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    protected function parseBody(Throwable $throwable): string
    {
        return Json::encode(
            [
                'errors' => [
                    FormattedError::createFromException(exception: $throwable, internalErrorMessage: $throwable->getMessage())
                ]
            ]
        );
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        if ($throwable instanceof HttpException) {
            return $throwable->getStatusCode();
        }

        if ($throwable->getCode()
            && is_int($throwable->getCode())
            && $throwable->getCode() >= 400
            && $throwable->getCode() < 600) {
            return $throwable->getCode();
        }

        return 500;
    }
}
