<?php

declare(strict_types=1);

namespace Menumbing\Orm\Event;

use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class ModelEvent
{
    public function __construct(public readonly Model $model)
    {
    }
}
