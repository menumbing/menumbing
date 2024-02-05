<?php

declare(strict_types=1);

namespace Menumbing\Orm\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsRepository extends AbstractAnnotation
{
    public function __construct(
        public readonly string $modelClass,
        public readonly ?string $serviceName = null
    ) {
    }
}
