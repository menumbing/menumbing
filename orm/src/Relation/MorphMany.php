<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\MorphMany as BaseMorphMany;
use Hyperf\Database\Model\Model as HyperfModel;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Exception\BadMethodCallException;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MorphMany extends BaseMorphMany
{
    /**
     * Add a child model instance to the parent model and save it after parent model saved.
     *
     * @param  HyperfModel  $model  The model to add.
     *
     * @return HyperfModel|Model The parent model that the model has been added to.
     *
     * @throws BadMethodCallException If the parent is not an instance of Model.
     */
    public function add(HyperfModel $model): HyperfModel|Model
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::AFTER_SAVE, function () use ($model) {
                $this->setForeignAttributesForCreate($model);
                $model->push();
            });

            return $this->parent;
        }

        throw BadMethodCallException::parentShouldInstanceOfModel($this->parent);
    }
}
