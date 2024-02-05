<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\HasMany as BaseHasMany;
use Hyperf\Database\Model\Model as HyperfModel;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Exception\BadMethodCallException;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class HasMany extends BaseHasMany
{
    /**
     * Add a child model to parent after parent model saved.
     *
     * @param  HyperfModel  $model  The Hyperf model to be added.
     *
     * @return HyperfModel|Model  The parent model with the added callback.
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

    /**
     * Remove a child model from parent before parent saved.
     *
     * @param  HyperfModel  $model  The instance of HyperfModel to be removed.
     *
     * @return HyperfModel|Model  The parent model instance if it exists, otherwise nothing is returned.
     */
    public function remove(HyperfModel $model): HyperfModel|Model
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::BEFORE_SAVE, fn() => $model->delete());

            return $this->parent;
        }

        throw BadMethodCallException::parentShouldInstanceOfModel($this->parent);
    }
}
