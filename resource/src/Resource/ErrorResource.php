<?php

declare(strict_types=1);

namespace Menumbing\Resource\Resource;

use Hyperf\Contract\Arrayable;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Resource\Json\JsonResource as BaseJsonResource;
use Menumbing\Resource\Trait\AdditionalMetadata;
use Psr\Http\Message\ResponseInterface;
use ReflectionObject;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ErrorResource extends BaseJsonResource
{
    use AdditionalMetadata;

    public ?string $wrap = 'error';

    public function __construct(mixed $resource)
    {
        if ($resource instanceof \Hyperf\Resource\Json\JsonResource) {
            $resource->wrap('error');
        }

        parent::__construct($resource);

        $this->withMetadata();
    }

    public function toArray(): array
    {
        if ($this->resource instanceof Arrayable) {
            return $this->resource->toArray();
        }

        return [
            'code'    => $this->getStatusCode(),
            'message' => $this->resource->getMessage(),
            'type'    => $this->getType($this->resource),
        ];
    }

    public function getStatusCode(): int
    {
        if (!$this->resource instanceof Throwable) {
            if ($this->resource instanceof ResponseInterface) {
                return $this->resource->getStatusCode();
            }

            return parent::getStatusCode();
        }

        $throwable = $this->resource;

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

    protected function getType(Throwable $throwable): string
    {
        return (new ReflectionObject($throwable))->getShortName();
    }
}
