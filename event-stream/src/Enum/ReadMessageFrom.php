<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Enum;

enum ReadMessageFrom
{
    case BEGINNING;
    case GROUP_CREATED;
}
