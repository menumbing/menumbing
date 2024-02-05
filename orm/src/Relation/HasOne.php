<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\HasOne as BaseHasOne;
use Menumbing\Orm\Trait\AssociateChild;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HasOne extends BaseHasOne
{
    use AssociateChild;
}
