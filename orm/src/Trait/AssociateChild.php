<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

use Hyperf\Database\Model\Model as HyperfModel;
use Hyperf\Database\Model\Relations\HasOneOrMany;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Exception\BadMethodCallException;
use Menumbing\Orm\Model;

/**
 * @mixin HasOneOrMany
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait AssociateChild
{
    /**
     * Associates a Hyperf Model with its parent Model and save it after parent model saved.
     *
     * @param  HyperfModel  $model  The Hyperf Model to associate with the parent Model.
     *
     * @return HyperfModel The parent Model.
     *
     * @throws BadMethodCallException When the parent is not an instance of Model.
     */
    public function associate(HyperfModel $model): HyperfModel|Model
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
