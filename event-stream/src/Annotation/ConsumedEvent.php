<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Annotation;

use Attribute;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ConsumedEvent extends ProducedEvent
{
}
