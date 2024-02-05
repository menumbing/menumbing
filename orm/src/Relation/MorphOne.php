<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\MorphOne as BaseMorphOne;
use Menumbing\Orm\Trait\AssociateChild;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MorphOne extends BaseMorphOne
{
    use AssociateChild;
}
