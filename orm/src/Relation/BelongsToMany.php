<?php

declare(strict_types=1);

namespace Menumbing\Orm\Relation;

use Hyperf\Database\Model\Relations\BelongsToMany as BaseBelongsToMany;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class BelongsToMany extends BaseBelongsToMany
{
    /**
     * Attach one or more related models to the parent model after parent saved.
     *
     * @param  mixed  $id  The ID of the related model.
     * @param  array  $attributes  [optional] Additional attributes to be set on the pivot table.
     * @param  bool  $touch  [optional] Indicates if the parent model's timestamps should be updated.
     *
     * @return void
     */
    public function attach($id, array $attributes = [], $touch = true): void
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::AFTER_SAVE, function () use ($id, $attributes, $touch) {
                parent::attach($id, $attributes, $touch);
            });

            return;
        }

        parent::attach($id, $attributes, $touch);
    }

    /**
     * Detach one or more related models from the parent model after parent saved.
     *
     * @param  mixed|null  $ids  The ID(s) of the related model(s) to detach.
     *                        If null, all related models will be detached.
     * @param  bool  $touch  Whether to update the timestamps on the related models.
     *                        Defaults to true.
     *
     * @return void
     */
    public function detach($ids = null, $touch = true): void
    {
        $this->call(fn() => parent::detach($ids, $touch));
    }

    /**
     * Sync one or more related models with the parent model after parent saved.
     *
     * @param  mixed  $ids  The ID(s) of the related model(s) to sync.
     * @param  bool  $detaching  Whether to detach any related models not in the provided ID list.
     *                           Defaults to true.
     *
     * @return void
     */
    public function sync($ids, $detaching = true): void
    {
        $this->call(fn() => parent::sync($ids, $detaching));
    }

    /**
     * Update the attributes of an existing pivot record after parent saved.
     *
     * @param  mixed  $id  The ID of the related model.
     * @param  array  $attributes  The new attributes to update.
     * @param  bool  $touch  Whether to update the timestamps on the related model.
     *                        Defaults to true.
     *
     * @return void
     */
    public function updateExistingPivot($id, array $attributes, $touch = true): void
    {
        $this->call(fn() => parent::updateExistingPivot($id, $attributes, $touch));
    }

    /**
     * Call a callable function after parent saved if parent is an instance of Model.
     *
     * @param  callable  $callback  The callable function to be called after parent saved.
     *
     * @return void
     */
    private function call(callable $callback): void
    {
        if ($this->parent instanceof Model) {
            $this->parent->addCallback(Callbacks::AFTER_SAVE, $callback);

            return;
        }

        $callback();
    }
}
