<?php

declare(strict_types=1);

namespace Menumbing\Resource\Trait;

use Hyperf\Resource\Json\JsonResource;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait ResourceOrigin
{
    public function getOrigin(mixed $resource): mixed
    {
        if ($resource instanceof JsonResource) {
            return $this->getOrigin($resource->resource);
        }

        return $resource;
    }
}
