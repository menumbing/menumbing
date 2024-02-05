<?php

declare(strict_types=1);

namespace Menumbing\Orm\Constant;

enum LockMode
{
    case PESSIMISTIC_WRITE;
    case PESSIMISTIC_READ;
}
