<?php

declare(strict_types=1);

namespace Menumbing\Resource\Resource;

use Hyperf\Resource\Json\JsonResource as BaseJsonResource;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ErrorResource extends BaseJsonResource
{
    public ?string $wrap = 'error';

    public function __construct(mixed $resource)
    {
        if ($resource instanceof \Hyperf\Resource\Json\JsonResource) {
            $resource->wrap('error');
        }

        parent::__construct($resource);
    }
}
