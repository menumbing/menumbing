<?php

declare(strict_types=1);

namespace Menumbing\Orm\Exception;

use Hyperf\Database\Model\Model as HyperfModel;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class BadMethodCallException extends \BadMethodCallException
{
    public static function parentShouldInstanceOfModel(HyperfModel $parent): static
    {
        return new static(sprintf(
            'Model "%s" should instance of "%s".',
            get_class($parent),
            Model::class
        ));
    }
}
