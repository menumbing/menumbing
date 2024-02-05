<?php

declare(strict_types=1);

namespace Menumbing\Orm;

use Menumbing\Orm\Constant\ActionType;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class Action
{
    public function __construct(public readonly Model $model, public readonly ActionType $type)
    {
    }

    public static function saveAction(Model $model): self
    {
        return new self($model, ActionType::SAVE);
    }

    public static function deleteAction(Model $model): Action
    {
        return new self($model, ActionType::DELETE);
    }
}
