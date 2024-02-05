<?php

declare(strict_types=1);

namespace Menumbing\Orm\Constant;

enum Callbacks: string
{
    case BEFORE_SAVE = 'BEFORE_SAVE';
    case AFTER_SAVE = 'AFTER_SAVE';
}
