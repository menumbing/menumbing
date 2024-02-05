<?php

declare(strict_types=1);

namespace Menumbing\Orm;

use Hyperf\Database\Model\Model as BaseModel;
use Hyperf\DbConnection\Traits\HasContainer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Model extends BaseModel
{
    use HasContainer;
    use Trait\HasAttributes;
    use Trait\HasCallbacks;
    use Trait\HasRelations;
}
