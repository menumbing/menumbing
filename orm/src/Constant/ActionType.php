<?php

declare(strict_types=1);

namespace Menumbing\Orm\Constant;

enum ActionType
{
    case SAVE;
    case DELETE;

    public function is(ActionType $type): bool
    {
        return $this === $type;
    }
}
