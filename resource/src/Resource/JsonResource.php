<?php

declare(strict_types=1);

namespace Menumbing\Resource\Resource;

use Hyperf\Resource\Json\JsonResource as BaseJsonResource;
use Menumbing\Resource\Trait\AdditionalMetadata;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class JsonResource extends BaseJsonResource
{
    use AdditionalMetadata;

    public function __construct(mixed $resource)
    {
        parent::__construct($resource);

        $this->withMetadata();
    }

    public function toArray(): array
    {
        if (is_scalar($this->resource)) {
            return [
                $this->wrap => $this->resource,
            ];
        }

        return parent::toArray();
    }
}
