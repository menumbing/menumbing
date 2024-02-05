<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HttpClient extends AbstractAnnotation
{
    public function __construct(public readonly string $httpClientId)
    {
    }
}
