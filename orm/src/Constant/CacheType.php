<?php

declare(strict_types=1);

namespace Menumbing\Orm\Constant;

enum CacheType: string
{
    case MODELS = 'models';
    case QUERIES = 'queries';
}
