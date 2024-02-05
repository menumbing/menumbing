<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\BelongsTo as BaseBelongsTo;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;
use Hyperf\Database\Model\Model as HyperfModel;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class BelongsTo extends BaseBelongsTo
{
    /**
     * Associates a model with the parent model after parent saved.
     *
     * @param Model $model  The model to associate with the parent model.
     *
     * @return Model|HyperfModel The associated child model.
     */
    public function associate($model): Model|HyperfModel
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::BEFORE_SAVE, function () use ($model) {
                $model->push();
                $this->child->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));
            });

            $this->child->setRelation($this->getRelationName(), $model);

            return $this->child;
        }

        return parent::associate($model);
    }
}
