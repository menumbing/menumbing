<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProducedEvent extends AbstractAnnotation
{
    public function __construct(
        public readonly string $stream,
        public readonly string $name,
        public readonly string $driver = 'default',
    ) {
    }
}
