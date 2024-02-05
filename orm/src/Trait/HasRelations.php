<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Menumbing\Orm\Relation\BelongsTo;
use Menumbing\Orm\Relation\BelongsToMany;
use Menumbing\Orm\Relation\HasMany;
use Menumbing\Orm\Relation\HasOne;
use Menumbing\Orm\Relation\MorphMany;
use Menumbing\Orm\Relation\MorphOne;
use Menumbing\Orm\Relation\MorphTo;

/**
 * @mixin Model
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait HasRelations
{
    abstract public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null);

    abstract public function hasOne($related, $foreignKey = null, $localKey = null);

    abstract public function morphOne($related, $name, $type = null, $id = null, $localKey = null);

    abstract public function morphTo($name = null, $type = null, $id = null, $ownerKey = null);

    abstract public function hasMany($related, $foreignKey = null, $localKey = null);

    abstract public function morphMany($related, $name, $type = null, $id = null, $localKey = null);

    abstract public function belongsToMany(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null
    );

    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation): BelongsTo
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    protected function newBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null
    ): BelongsToMany {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey): HasMany
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey): HasOne
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey): MorphMany
    {
        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey): MorphOne
    {
        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation): MorphTo
    {
        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }
}
