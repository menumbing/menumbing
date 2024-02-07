<?php

declare(strict_types=1);

namespace Menumbing\Resource\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS| Attribute::TARGET_METHOD)]
class WithResource extends AbstractAnnotation
{
    public function __construct(public ?string $resource = null, public int $statusCode = 200)
    {
    }
}
