<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\MorphTo as BaseMorphTo;
use Hyperf\Database\Model\Model as HyperfModel;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MorphTo extends BaseMorphTo
{
    public function associate($model): HyperfModel|Model
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::BEFORE_SAVE, function () use ($model) {
                $this->parent->setAttribute($this->foreignKey, $model instanceof HyperfModel ? $model->getKey() : null);
                $this->parent->setAttribute($this->morphType, $model instanceof HyperfModel ? $model->getMorphClass() : null);
            });

            return $this->parent->setRelation($this->getRelationName(), $model);
        }

        return parent::associate($model);
    }
}
