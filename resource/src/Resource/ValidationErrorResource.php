<?php

declare(strict_types=1);

namespace Menumbing\Resource\Resource;

use Hyperf\Validation\ValidationException;

/**
 * @property ValidationException $resource
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ValidationErrorResource extends ErrorResource
{
    public function __construct(ValidationException $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'fails' => $this->resource->errors()
        ];
    }

    public function getStatusCode(): int
    {
        return 422;
    }
}
