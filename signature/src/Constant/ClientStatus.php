<?php

declare(strict_types=1);

namespace Menumbing\Signature\Constant;

enum ClientStatus: string
{
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
}
