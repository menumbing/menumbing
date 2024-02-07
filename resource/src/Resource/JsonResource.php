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
}
